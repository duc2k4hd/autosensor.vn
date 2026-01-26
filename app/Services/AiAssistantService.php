<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiAssistantService
{
    public function __construct(
        private ?string $apiKey = null,
        private ?string $model = null,
        private ?int $timeout = null
    ) {
        $this->apiKey = $this->apiKey ?? config('services.gemini.key');
        $this->model = $this->model ?? config('services.gemini.model', 'gemini-1.5-flash');
        $this->timeout = $this->timeout ?? (int) config('services.gemini.timeout', 25);
    }

    /**
     * @param  array<int, array{role:string, content:string}>  $history
     * @param  array<string, mixed>  $pageContext
     * @return array{answer:string,references:array<string, array<int, array<string, string|float|null>>>}
     */
    public function answer(string $question, ?Account $account = null, array $history = [], array $pageContext = []): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('Chưa cấu hình khoá API cho Gemini.');
        }

        $question = trim($question);

        $context = $this->buildContext($question, $pageContext);
        $historyText = $this->buildHistoryText($history);

        $payload = $this->buildPayload(
            $question,
            $context['text'],
            $historyText,
            $account
        );

        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($this->endpoint(), $payload);
        } catch (\Throwable $e) {
            Log::error('Gemini API unreachable', ['message' => $e->getMessage()]);

            // Fallback: trả lời thân thiện thay vì ném 500
            return $this->buildFallbackAnswer(
                'Máy chủ AI đang bận hoặc không phản hồi. Bạn vui lòng thử lại sau vài phút hoặc liên hệ hotline/Zalo để được hỗ trợ trực tiếp.',
                $context
            );
        }

        if (! $response->successful()) {
            $status = $response->status();
            $body = $response->json() ?? [];

            Log::warning('Gemini API responded with error', [
                'status' => $status,
                'body' => $response->body(),
            ]);

            // Nếu bị giới hạn 429 hoặc RESOURCE_EXHAUSTED → trả lời nhẹ nhàng, không ném exception
            if ($status === 429 || ($body['error']['status'] ?? null) === 'RESOURCE_EXHAUSTED') {
                return $this->buildFallbackAnswer(
                    'Hôm nay trợ lý AI đã đạt giới hạn sử dụng tạm thời trên máy chủ. Bạn có thể thử lại sau vài phút hoặc liên hệ trực tiếp hotline/Zalo ở góc màn hình để được tư vấn nhanh.',
                    $context
                );
            }

            throw new \RuntimeException('Không thể lấy câu trả lời ngay bây giờ. Bạn vui lòng thử lại sau.');
        }

        $answer = $this->extractAnswer($response->json());
        $answer = $this->stripMarkdownLinks($answer);

        return [
            'answer' => $answer,
            'references' => [
                'products' => $context['products'],
                'posts' => $context['posts'],
            ],
        ];
    }

    /**
     * Trả về câu trả lời fallback khi AI bên ngoài lỗi/rate-limit.
     *
     * @param  array{text:string,products:array<int, array<string, mixed>>,posts:array<int, array<string, mixed>>}  $context
     * @return array{answer:string,references:array<string, array<int, array<string, string|float|null>>>}
     */
    private function buildFallbackAnswer(string $message, array $context): array
    {
        return [
            'answer' => $message,
            'references' => [
                'products' => $context['products'],
                'posts' => $context['posts'],
            ],
        ];
    }

    private function endpoint(): string
    {
        return sprintf(
            'https://generativelanguage.googleapis.com/v1/models/%s:generateContent?key=%s',
            $this->model,
            $this->apiKey
        );
    }

    /**
     * @param  array<string, mixed>  $pageContext
     * @return array{text:string,products:array<int, array<string, mixed>>,posts:array<int, array<string, mixed>>}
     */
    private function buildContext(string $question, array $pageContext = []): array
    {
        $keywords = $this->extractKeywords($question);

        $focusedProducts = collect();
        $focusedPosts = collect();

        // Ưu tiên sản phẩm đang xem ở trang chi tiết (đọc đầy đủ tên/mô tả/thông số hiển thị)
        if (($pageContext['page'] ?? null) === 'product_detail') {
            $productId = $pageContext['product_id'] ?? null;
            $productSlug = $pageContext['product_slug'] ?? null;

            $query = Product::query()->active();
            if ($productId) {
                $query->where('id', $productId);
            } elseif ($productSlug) {
                $query->where('slug', $productSlug);
            }

            $focused = $query
                ->select([
                    'id',
                    'name',
                    'slug',
                    'sku',
                    'short_description',
                    'description',
                    'price',
                    'sale_price',
                    'stock_quantity',
                    'primary_category_id',
                ])
                ->with(['primaryCategory', 'brand', 'variants'])
                ->first();

            if ($focused) {
                $focusedProducts->push($focused);
            }
        }

        // Blog post nếu có
        if (($pageContext['page'] ?? null) === 'blog_post') {
            $postId = $pageContext['post_id'] ?? null;
            $postSlug = $pageContext['post_slug'] ?? null;

            $postQuery = Post::query()->published();
            if ($postId) {
                $postQuery->where('id', $postId);
            } elseif ($postSlug) {
                $postQuery->where('slug', $postSlug);
            }

            $focusedPost = $postQuery
                ->select(['id', 'title', 'slug', 'excerpt', 'content', 'category_id'])
                ->with('category')
                ->first();

            if ($focusedPost) {
                $focusedPosts->push($focusedPost);
            }
        }

        $pageType = (string) ($pageContext['page'] ?? '');
        $categoryIds = isset($pageContext['category_ids']) && is_array($pageContext['category_ids'])
            ? array_values(array_filter(array_map('intval', $pageContext['category_ids'])))
            : [];

        // Mặc định tìm theo keyword, nhưng tùy trang sẽ siết phạm vi
        if (in_array($pageType, ['home', 'generic', 'shop'], true)) {
            // Trang chủ & các trang khác (trừ bài viết/sản phẩm/danh mục): chỉ nói chung về thương hiệu
            $foundProducts = collect();
            $foundPosts = collect();
        } elseif ($pageType === 'category' && ! empty($categoryIds)) {
            // Trang danh mục: chỉ lấy sản phẩm trong danh mục đó
            $foundProducts = $this->searchProducts($keywords, $categoryIds);
            $foundPosts = collect();
        } else {
            $foundProducts = $this->searchProducts($keywords);
            $foundPosts = $this->searchPosts($keywords);
        }

        // Gộp sản phẩm ưu tiên + kết quả tìm kiếm, tránh trùng ID
        $products = $focusedProducts->concat(
            $foundProducts->reject(fn (Product $p) => $focusedProducts->contains('id', $p->id))
        );

        $posts = $focusedPosts->concat(
            $foundPosts->reject(fn (Post $p) => $focusedPosts->contains('id', $p->id))
        );

        $contextParts = [];

        // Thêm ngữ cảnh trang hiện tại cho model hiểu
        if (! empty($pageContext['page'])) {
            $pageTitle = (string) ($pageContext['title'] ?? '');
            $pageUrl = (string) ($pageContext['url'] ?? '');

            if ($pageType === 'product_detail' && $focusedProducts->isNotEmpty()) {
                /** @var Product $current */
                $current = $focusedProducts->first();
                $desc = trim(strip_tags((string) ($current->description ?? '')));
                $short = trim(strip_tags((string) ($current->short_description ?? '')));
                $desc = mb_substr($desc, 0, 1200);
                $short = mb_substr($short, 0, 500);

                $variantLines = '';
                try {
                    $current->loadMissing('variants');
                    if ($current->variants && $current->variants->isNotEmpty()) {
                        $variantLines = $current->variants
                            ->take(10)
                            ->map(function ($v) {
                                $price = $v->sale_price ?? $v->price ?? null;
                                $stock = $v->stock_quantity;
                                return "- {$v->name} | Giá: ".($price !== null ? number_format((float) $price, 0, ',', '.').'₫' : 'liên hệ')." | Kho: ".($stock === null ? 'không rõ' : $stock);
                            })
                            ->implode("\n");
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
                $contextParts[] = sprintf(
                    "NGỮ CẢNH TRANG HIỆN TẠI (BẮT BUỘC TUÂN THỦ):\nNgười dùng đang MỞ TRANG CHI TIẾT SẢN PHẨM trên website.\n\nDỮ LIỆU SẢN PHẨM ĐANG XEM:\n- Tên: %s\n- SKU: %s\n- Thương hiệu: %s\n- Danh mục: %s\n- Giá: %s\n- Giá KM: %s\n- Tồn kho: %s\n- Mô tả ngắn: %s\n- Mô tả/Thông số: %s\n%s\n\nYÊU CẦU TRẢ LỜI:\n- Chỉ trả lời dựa trên DỮ LIỆU SẢN PHẨM ĐANG XEM ở trên.\n- Nếu câu hỏi nằm ngoài dữ liệu này (không có trong mô tả/thông số/giá/biến thể), hãy nói: \"Mình chưa thấy thông tin đó trong mô tả/thông số của sản phẩm này\" và gợi ý khách để lại SĐT/Zalo để kỹ thuật tư vấn.\n- Trả lời NGẮN GỌN, đủ ý (2-6 câu), không lan man.",
                    $current->name ?? 'Chưa rõ',
                    $current->sku ?? 'Chưa rõ',
                    $current->brand?->name ?? 'Chưa rõ',
                    $current->primaryCategory?->name ?? 'Chưa rõ',
                    $current->price !== null ? number_format((float) $current->price, 0, ',', '.').'₫' : 'liên hệ',
                    $current->sale_price ? number_format((float) $current->sale_price, 0, ',', '.').'₫' : 'không',
                    $current->stock_quantity !== null ? (string) $current->stock_quantity : 'không rõ',
                    $short ?: 'không có',
                    $desc ?: 'không có',
                    $variantLines ? ("\nBIẾN THỂ (tối đa 10):\n".$variantLines) : '',
                    $pageUrl ?: route('client.product.detail', $current->slug)
                );
            } elseif ($pageType === 'blog_post' && $focusedPosts->isNotEmpty()) {
                /** @var Post $post */
                $post = $focusedPosts->first();
                $postContent = trim(strip_tags((string) ($post->content ?? '')));
                $postContent = mb_substr($postContent, 0, 2500);
                $contextParts[] = sprintf(
                    "NGỮ CẢNH TRANG HIỆN TẠI (BẮT BUỘC TUÂN THỦ):\nNgười dùng đang đọc BÀI VIẾT trên website.\n\nBÀI VIẾT ĐANG XEM:\n- Tiêu đề: %s\n- Chủ đề: %s\n- URL: %s\n\nNỘI DUNG BÀI VIẾT (trích, để trả lời):\n%s\n\nYÊU CẦU TRẢ LỜI:\n- Nếu người dùng hỏi tóm tắt: trả lời 3-6 câu, gạch đầu dòng nếu cần.\n- Nếu hỏi chi tiết: trả lời NGẮN GỌN, bám đúng nội dung bài viết.\n- Không tự bịa thông tin ngoài bài viết.",
                    $post->title ?? ($pageTitle ?: 'Bài viết trên AutoSensor.vn'),
                    $post->category?->name ?? 'Chưa phân loại',
                    $pageUrl ?: route('client.blog.show', $post->slug),
                    $postContent ?: '(Bài viết chưa có nội dung để trích)'
                );
            } elseif ($pageType === 'category') {
                $catName = (string) ($pageContext['category_name'] ?? $pageTitle ?: 'Danh mục');
                $contextParts[] = sprintf(
                    "NGỮ CẢNH TRANG HIỆN TẠI:\nNgười dùng đang xem DANH MỤC sản phẩm: %s\nURL: %s\n\nYÊU CẦU TRẢ LỜI:\n- Gợi ý sản phẩm và tư vấn chỉ trong danh mục này.\n- Trả lời ngắn gọn 2-6 câu.\n- Nếu cần gợi ý sản phẩm: ưu tiên các sản phẩm trong 'Sản phẩm liên quan' (sources).",
                    $catName,
                    $pageUrl ?: url()->current()
                );
            } elseif ($pageType === 'home' || $pageType === 'generic' || $pageType === 'shop') {
                $introUrl = route('client.introduction.index');
                $contextParts[] = sprintf(
                    "NGỮ CẢNH TRANG HIỆN TẠI:\nNgười dùng đang ở trang %s.\n\nYÊU CẦU TRẢ LỜI:\n- Chỉ tư vấn CHUNG về thương hiệu %s (dịch vụ, nhóm sản phẩm, hỗ trợ kỹ thuật), không đi sâu 1 sản phẩm cụ thể.\n- Nếu người dùng muốn thông tin chính xác về thương hiệu/đơn vị vận hành: hãy dẫn tới trang giới thiệu %s.\n- Trả lời ngắn gọn 2-6 câu.",
                    $pageTitle ?: 'AutoSensor.vn',
                    config('app.name', 'AutoSensor Việt Nam'),
                    $introUrl
                );
            } else {
                $contextParts[] = sprintf(
                    "Người dùng đang duyệt trang: %s\nURL: %s\nHãy trả lời tập trung, ngắn gọn, liên quan tới nội dung trang và các sản phẩm/dịch vụ của AutoSensor Việt Nam.",
                    $pageTitle ?: 'Trang trên AutoSensor.vn',
                    $pageUrl ?: url()->current()
                );
            }
        }

        if ($products->isNotEmpty()) {
            $productsText = $products->map(function (Product $product) {
                $price = $this->formatPrice($product);

                // Lấy stock từ variant nếu có, nếu không thì từ product
                $product->loadMissing('variants');
                $hasVariants = $product->hasVariants();

                if ($hasVariants) {
                    $variants = $product->variants;
                    $stockInfo = $variants->map(function ($variant) {
                        $stock = $variant->stock_quantity !== null
                            ? $variant->stock_quantity.' sản phẩm'
                            : 'không giới hạn';

                        return "{$variant->name}: {$stock}";
                    })->implode(', ');
                    $stock = $stockInfo ?: 'chưa xác định';
                } else {
                    $stock = $product->stock_quantity !== null
                        ? $product->stock_quantity.' sản phẩm'
                        : 'chưa xác định';
                }

                return "- {$product->name}".($hasVariants ? ' (có biến thể)' : '')." | Giá: {$price} | Kho: {$stock} | Liên kết: ".route('client.product.detail', $product->slug);
            })->implode("\n");

            $contextParts[] = "Sản phẩm liên quan:\n{$productsText}";
        }

        if ($posts->isNotEmpty()) {
            $postsText = $posts->map(function (Post $post) {
                return "- {$post->title} | Chủ đề: ".($post->category?->name ?? 'Chưa phân loại').' | Liên kết: '.route('client.blog.show', $post->slug);
            })->implode("\n");

            $contextParts[] = "Bài viết liên quan:\n{$postsText}";
        }

        if (empty($contextParts)) {
            $contextParts[] = 'Hiện chưa có dữ liệu nội bộ phù hợp, hãy trả lời dựa trên kiến thức về thiết bị tự động hóa công nghiệp, bán hàng và dịch vụ kỹ thuật của AutoSensor Việt Nam.';
        } else {
            // Thêm cảnh báo nếu có sản phẩm nhưng AI có thể bỏ qua
            if ($products->isNotEmpty()) {
                $contextParts[] = 'QUAN TRỌNG: Danh sách sản phẩm trên là dữ liệu thực từ hệ thống. Bạn PHẢI trả lời dựa trên danh sách này. KHÔNG được nói "không có sản phẩm" hoặc "chưa có sản phẩm".';
            }
        }

        $postRefs = $posts->map(fn (Post $post) => $this->transformPost($post))->all();
        if (in_array($pageType, ['home', 'generic', 'shop'], true)) {
            $postRefs[] = [
                'title' => 'Giới thiệu AutoSensor Việt Nam',
                'url' => route('client.introduction.index'),
            ];
        }

        return [
            'text' => implode("\n\n", $contextParts),
            'products' => $products->map(fn (Product $product) => $this->transformProduct($product))->all(),
            'posts' => $postRefs,
        ];
    }

    /**
     * @param  array<int, string>  $keywords
     */
    private function searchProducts(array $keywords, array $categoryIds = []): Collection
    {
        $query = Product::query()
            ->active()
            ->select(['id', 'name', 'slug', 'sku', 'short_description', 'price', 'sale_price', 'stock_quantity', 'primary_category_id'])
            ->with('primaryCategory');

        if (! empty($categoryIds)) {
            $query->inCategory($categoryIds);
        }

        if (! empty($keywords)) {
            $query->where(function ($q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $q->orWhere('name', 'like', "%{$keyword}%")
                        ->orWhere('slug', 'like', "%{$keyword}%")
                        ->orWhere('sku', 'like', "%{$keyword}%")
                        ->orWhere('short_description', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%")
                        ->orWhereHas('primaryCategory', function ($catQuery) use ($keyword) {
                            $catQuery->where('name', 'like', "%{$keyword}%");
                        });
                }
            });

            // Thêm tìm kiếm theo tất cả keywords cùng lúc (AND logic) để tìm chính xác hơn
            if (count($keywords) > 1) {
                $query->orWhere(function ($q) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        $q->where(function ($subQ) use ($keyword) {
                            $subQ->where('name', 'like', "%{$keyword}%")
                                ->orWhere('slug', 'like', "%{$keyword}%")
                                ->orWhere('short_description', 'like', "%{$keyword}%")
                                ->orWhere('description', 'like', "%{$keyword}%");
                        });
                    }
                });
            }
        }

        $products = $query->orderByDesc('is_featured')->latest('updated_at')->limit(10)->get();

        // Nếu không tìm thấy, thử tìm kiếm mở rộng hơn (tách từ khóa thành các phần nhỏ hơn)
        if ($products->isEmpty() && ! empty($keywords)) {
            $expandedKeywords = [];
            foreach ($keywords as $keyword) {
                if (mb_strlen($keyword) > 3) {
                    // Thử tìm với các phần của từ khóa
                    $expandedKeywords[] = mb_substr($keyword, 0, -1);
                    $expandedKeywords[] = mb_substr($keyword, 1);
                }
            }

            if (! empty($expandedKeywords)) {
                $expandedQuery = Product::query()
                    ->active()
                    ->select(['id', 'name', 'slug', 'sku', 'short_description', 'price', 'sale_price', 'stock_quantity', 'primary_category_id'])
                    ->with('primaryCategory')
                    ->where(function ($q) use ($expandedKeywords) {
                        foreach ($expandedKeywords as $keyword) {
                            $q->orWhere('name', 'like', "%{$keyword}%")
                                ->orWhere('slug', 'like', "%{$keyword}%")
                                ->orWhere('short_description', 'like', "%{$keyword}%");
                        }
                    });

                $products = $expandedQuery->orderByDesc('is_featured')->latest('updated_at')->limit(10)->get();
            }
        }

        // Fallback: lấy sản phẩm nổi bật nếu vẫn không tìm thấy
        if ($products->isEmpty()) {
            $products = Product::query()
                ->active()
                ->orderByDesc('is_featured')
                ->latest('updated_at')
                ->limit(5)
                ->select(['id', 'name', 'slug', 'sku', 'short_description', 'price', 'sale_price', 'stock_quantity', 'primary_category_id'])
                ->with('primaryCategory')
                ->get();
        }

        Log::info('AI Product Search', [
            'keywords' => $keywords,
            'found_count' => $products->count(),
            'product_ids' => $products->pluck('id')->toArray(),
        ]);

        return $products;
    }

    /**
     * @param  array<int, string>  $keywords
     */
    private function searchPosts(array $keywords): Collection
    {
        $query = Post::query()
            ->published()
            ->select(['id', 'title', 'slug', 'excerpt', 'category_id'])
            ->with('category');

        if (! empty($keywords)) {
            $query->where(function ($q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $q->orWhere('title', 'like', "%{$keyword}%")
                        ->orWhere('excerpt', 'like', "%{$keyword}%")
                        ->orWhere('content', 'like', "%{$keyword}%");
                }
            });
        }

        $posts = $query->limit(5)->get();

        if ($posts->isEmpty()) {
            $posts = Post::query()
                ->published()
                ->latest('published_at')
                ->limit(5)
                ->with('category')
                ->get();
        }

        return $posts;
    }

    /**
     * @return array<int, string>
     */
    private function extractKeywords(string $question): array
    {
        $normalized = Str::of($question)
            ->lower()
            ->replaceMatches('/[^0-9a-zà-ỹ\\s]/u', ' ')
            ->squish();

        if ($normalized->isEmpty()) {
            return [];
        }

        $words = collect(preg_split('/\\s+/', (string) $normalized))
            ->filter(fn ($word) => mb_strlen((string) $word) >= 2) // Giảm từ 3 xuống 2 để không bỏ sót từ quan trọng
            ->unique()
            ->values()
            ->all();

        // Thêm các cụm từ (bigrams) để tìm kiếm chính xác hơn
        $bigrams = [];
        for ($i = 0; $i < count($words) - 1; $i++) {
            $bigram = $words[$i].' '.$words[$i + 1];
            if (mb_strlen($bigram) >= 4) {
                $bigrams[] = $bigram;
            }
        }

        // Kết hợp từ đơn và cụm từ, ưu tiên cụm từ
        $keywords = array_merge($bigrams, $words);

        return collect($keywords)
            ->unique()
            ->take(12) // Tăng từ 8 lên 12 để có nhiều từ khóa hơn
            ->values()
            ->all();
    }

    private function buildHistoryText(array $history): string
    {
        if (empty($history)) {
            return '';
        }

        return collect($history)
            ->map(fn ($item) => strtoupper($item['role']).': '.$item['content'])
            ->implode("\n");
    }

    private function buildPayload(string $question, string $contextText, string $historyText, ?Account $account = null): array
    {
        $hasProducts = str_contains($contextText, 'Sản phẩm liên quan:') && ! str_contains($contextText, 'Hiện chưa có dữ liệu nội bộ phù hợp');

        $systemPrompt = <<<'PROMPT'
        Bạn là trợ lý AI của thương hiệu AutoSensor Việt Nam. 

        QUY TẮC QUAN TRỌNG:
        1. BẮT BUỘC: Nếu trong phần "Sản phẩm liên quan" có danh sách sản phẩm, bạn PHẢI trả lời dựa trên các sản phẩm đó. KHÔNG được nói "không có sản phẩm" hoặc "chưa có sản phẩm" khi đã có danh sách.
        2. BẮT BUỘC: Chỉ đề xuất các sản phẩm có trong danh sách "Sản phẩm liên quan". KHÔNG được bịa đặt hoặc đề xuất sản phẩm không có trong danh sách.
        3. BẮT BUỘC: Chỉ sử dụng thông tin (tên, giá, mô tả) từ danh sách được cung cấp. KHÔNG được bịa đặt thông tin.
        4. Nếu khách hàng đang ở trang chi tiết một sản phẩm và câu hỏi không yêu cầu so sánh, hãy TẬP TRUNG giải thích, tư vấn chi tiết cho CHÍNH sản phẩm đó (tính năng, ứng dụng, cấu hình, lưu ý lắp đặt...). Chỉ gợi ý sang sản phẩm khác ở phần cuối nếu thật sự phù hợp với câu hỏi.
        5. Nếu khách hàng cần so sánh / thay thế, lúc đó mới so sánh giữa sản phẩm hiện tại và các sản phẩm khác trong danh sách.
        6. Khi không có dữ liệu nội bộ phù hợp (phần "Hiện chưa có dữ liệu nội bộ phù hợp"), bạn mới cung cấp lời khuyên tổng quát về thiết bị tự động hóa, giải pháp công nghiệp, ứng dụng tự động hóa.
        7. Giữ văn phong thân thiện, súc tích và ưu tiên tiếng Việt.
        8. Luôn kèm theo link sản phẩm khi đề xuất (link đã có trong danh sách).
        9. QUAN TRỌNG: Trả lời ĐẦY ĐỦ và HOÀN CHỈNH. Không được cắt câu trả lời giữa chừng. Nếu có danh sách sản phẩm, hãy giới thiệu từng sản phẩm một cách đầy đủ với tên, giá, và link.
        PROMPT;

        if ($hasProducts) {
            $systemPrompt .= "\n\nLƯU Ý: Hiện tại có sản phẩm trong danh sách. Bạn PHẢI trả lời dựa trên danh sách này và KHÔNG được nói không có sản phẩm.";
        }

        // Lưu ý: phải dùng array_values(...) để reindex về mảng tuần tự 0,1,2,...
        // Nếu không, json_encode sẽ biến thành object với key "0","2","3"... và Gemini báo lỗi "Unknown name \"0\" at 'contents[0].parts'".
        $parts = array_values(array_filter([
            ['text' => $systemPrompt],
            $account ? ['text' => 'Thông tin người dùng: '.$account->name.' - '.$account->email] : null,
            $contextText ? ['text' => "Dữ liệu nội bộ:\n{$contextText}"] : null,
            $historyText ? ['text' => "Lược sử hội thoại:\n{$historyText}"] : null,
            ['text' => "Câu hỏi khách hàng: {$question}"],
        ]));

        return [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => $parts,
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.4,
                'topP' => 0.8,
                'topK' => 32,
                'maxOutputTokens' => 2048, // Tăng từ 512 lên 2048 để trả lời đầy đủ hơn
            ],
        ];
    }

    private function extractAnswer(array $response): string
    {
        $text = data_get($response, 'candidates.0.content.parts.0.text');

        if (! $text) {
            Log::warning('Gemini API missing answer', ['response' => $response]);

            throw new \RuntimeException('Chưa nhận được câu trả lời từ AI.');
        }

        return trim((string) $text);
    }

    private function stripMarkdownLinks(string $text): string
    {
        // Thay [label](url) -> label (giữ text, bỏ link)
        return preg_replace('/\[(.*?)\]\((.*?)\)/', '$1', $text);
    }

    /**
     * @return array<string, string|null>
     */
    private function transformProduct(Product $product): array
    {
        return [
            'id' => (string) $product->id,
            'name' => $product->name,
            'url' => route('client.product.detail', $product->slug),
            'category' => $product->primaryCategory?->name,
            'price' => $this->formatPrice($product),
            'short_description' => Str::limit(strip_tags((string) $product->short_description), 120),
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function transformPost(Post $post): array
    {
        return [
            'id' => (string) $post->id,
            'title' => $post->title,
            'url' => route('client.blog.show', $post->slug),
            'category' => $post->category?->name,
            'excerpt' => Str::limit(strip_tags((string) $post->excerpt), 140),
        ];
    }

    private function formatPrice(Product $product): string
    {
        $price = $product->sale_price && $product->sale_price > 0
            ? $product->sale_price
            : $product->price;

        if (! $price) {
            return 'Liên hệ';
        }

        return number_format((float) $price, 0, ',', '.').'đ';
    }
}
