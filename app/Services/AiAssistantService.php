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
     * @return array{answer:string,references:array<string, array<int, array<string, string|float|null>>>}
     */
    public function answer(string $question, ?Account $account = null, array $history = []): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('Chưa cấu hình khoá API cho Gemini.');
        }

        $question = trim($question);

        $context = $this->buildContext($question);
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
            throw new \RuntimeException('Máy chủ AI đang bận. Vui lòng thử lại sau ít phút.');
        }

        if (! $response->successful()) {
            Log::warning('Gemini API responded with error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('Không thể lấy câu trả lời ngay bây giờ. Bạn vui lòng thử lại sau.');
        }

        $answer = $this->extractAnswer($response->json());

        return [
            'answer' => $answer,
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
     * @return array{text:string,products:array<int, array<string, mixed>>,posts:array<int, array<string, mixed>>}
     */
    private function buildContext(string $question): array
    {
        $keywords = $this->extractKeywords($question);
        $products = $this->searchProducts($keywords);
        $posts = $this->searchPosts($keywords);

        $contextParts = [];

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

        return [
            'text' => implode("\n\n", $contextParts),
            'products' => $products->map(fn (Product $product) => $this->transformProduct($product))->all(),
            'posts' => $posts->map(fn (Post $post) => $this->transformPost($post))->all(),
        ];
    }

    /**
     * @param  array<int, string>  $keywords
     */
    private function searchProducts(array $keywords): Collection
    {
        $query = Product::query()
            ->active()
            ->select(['id', 'name', 'slug', 'sku', 'short_description', 'price', 'sale_price', 'stock_quantity', 'primary_category_id'])
            ->with('primaryCategory');

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
4. Nếu khách hàng hỏi về sản phẩm cụ thể mà không có trong danh sách, hãy đề xuất các sản phẩm tương tự từ danh sách có sẵn.
5. Khi không có dữ liệu nội bộ phù hợp (phần "Hiện chưa có dữ liệu nội bộ phù hợp"), bạn mới cung cấp lời khuyên tổng quát về thiết bị tự động hóa, giải pháp công nghiệp, ứng dụng tự động hóa.
6. Giữ văn phong thân thiện, súc tích và ưu tiên tiếng Việt.
7. Luôn kèm theo link sản phẩm khi đề xuất (link đã có trong danh sách).
8. QUAN TRỌNG: Trả lời ĐẦY ĐỦ và HOÀN CHỈNH. Không được cắt câu trả lời giữa chừng. Nếu có danh sách sản phẩm, hãy giới thiệu từng sản phẩm một cách đầy đủ với tên, giá, và link.
PROMPT;

        if ($hasProducts) {
            $systemPrompt .= "\n\nLƯU Ý: Hiện tại có sản phẩm trong danh sách. Bạn PHẢI trả lời dựa trên danh sách này và KHÔNG được nói không có sản phẩm.";
        }

        $parts = array_filter([
            ['text' => $systemPrompt],
            $account ? ['text' => 'Thông tin người dùng: '.$account->name.' - '.$account->email] : null,
            $contextText ? ['text' => "Dữ liệu nội bộ:\n{$contextText}"] : null,
            $historyText ? ['text' => "Lược sử hội thoại:\n{$historyText}"] : null,
            ['text' => "Câu hỏi khách hàng: {$question}"],
        ]);

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
