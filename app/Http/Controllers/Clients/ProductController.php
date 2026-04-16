<?php

namespace App\Http\Controllers\Clients;

use App\Helpers\CategoryHelper;
use App\Http\Controllers\Controller;
use App\Jobs\RecordProductView;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Favorite;
use App\Models\PopupContent;
use App\Models\Product;
use App\Models\ProductSlugHistory;
use App\Models\SupportStaff;
use App\Models\Tag;
use App\Models\Voucher;
use App\Services\ProductViewService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function __construct(
        private ProductViewService $productViewService
    ) {}

    public function detail($slug)
    {

        // Tối ưu: Cache cả product và category check để tránh query không cần thiết
        // Key cache: 'slug_type_' để phân biệt product/category/not_found
        $cacheKey = 'slug_type_'.$slug;

        try {
            // Dùng slug_index để resolve 1 phát, không phải check tuần tự
            // Cache 1 giờ, fallback sang UNION ALL cũ nếu chưa có dữ liệu slug_index (đảm bảo không 404 oan)
            $slugType = Cache::remember($cacheKey, 3600, function () use ($slug) {
                $index = \App\Models\SlugIndex::where('slug', $slug)
                    ->where('is_active', true)
                    ->select('type', 'entity_id', 'target_slug')
                    ->first();

                if ($index) {
                    return [
                        'type' => $index->type,
                        'entity_id' => $index->entity_id,
                        'target_slug' => $index->target_slug,
                    ];
                }

                // Fallback: UNION ALL để không gián đoạn khi slug_index chưa được seed đầy đủ
                $sql = "
                    (
                        SELECT id, 'product' AS type, NULL AS target_slug
                        FROM products
                        WHERE slug = ? AND is_active = 1
                        LIMIT 1
                    )
                    UNION ALL
                    (
                        SELECT id, 'post' AS type, NULL AS target_slug
                        FROM posts
                        WHERE slug = ? AND status = 'published'
                        LIMIT 1
                    )
                    UNION ALL
                    (
                        SELECT id, 'category' AS type, NULL AS target_slug
                        FROM categories
                        WHERE slug = ? AND is_active = 1
                        LIMIT 1
                    )
                    LIMIT 1
                ";

                $result = DB::selectOne($sql, [$slug, $slug, $slug]);

                if (! $result) {
                    return ['type' => 'not_found', 'entity_id' => null, 'target_slug' => null];
                }

                return [
                    'type' => $result->type ?? 'not_found',
                    'entity_id' => $result->id ?? null,
                    'target_slug' => $result->target_slug ?? null,
                ];
            });

            // Nếu là bài viết, redirect sang route bài viết chuẩn
            if ($slugType['type'] === 'post') {
                // Không load thêm gì, chỉ điều hướng đúng URL bài viết
                return redirect()->route('client.blog.show', ['post' => $slug], 301);
            }

            // Nếu là category, forward ngay đến ShopController (không query product)
            if ($slugType['type'] === 'category') {
                $shopController = app(ShopController::class);

                return $shopController->index(request(), $slug);
            }

            // Nếu không tìm thấy, check history và category-brand combo
            if ($slugType['type'] === 'not_found') {
                // Check slug history trước
                $history = Cache::remember('slug_history_'.$slug, 86400, function () use ($slug) {
                    return ProductSlugHistory::where('slug', $slug)
                        ->select('product_id')
                        ->first();
                });

                if ($history) {
                    $newProduct = Product::active()
                        ->select('slug')
                        ->find($history->product_id);
                    if ($newProduct) {
                        // Invalidate cache và redirect
                        Cache::forget($cacheKey);

                        return redirect()->route('client.product.detail', $newProduct->slug, 301);
                    }
                }

                // Nếu slug có dấu gạch ngang, có thể là category-brand combo
                // Forward về ShopController để check
                if (strpos($slug, '-') !== false) {
                    $parts = explode('-', $slug, 2);
                    if (count($parts) === 2) {
                        $shopController = app(ShopController::class);
                        try {
                            return $shopController->categoryBrand($parts[0], $parts[1], request());
                        } catch (\Exception $e) {
                            // Nếu categoryBrand fail, tiếp tục return 404
                        }
                    }
                }

                return view('clients.pages.errors.404');
            }

            // Nếu là product, load đầy đủ với cache
            // Cache forever với tag để có thể invalidate khi cần (thông qua clearProductDetailCache)
            try {
                $product = Cache::rememberForever('product_detail_'.$slug, function () use ($slug) {
                    // Query với composite index (slug, is_active) - rất nhanh với hàng triệu records
                    $product = Product::where('slug', $slug)
                        ->active()
                        ->select($this->productDetailSelectColumns())
                        ->first();

                    if ($product) {
                        $this->ensureProductDetailRelations($product);
                    }

                    return $product;
                });
            } catch (\Throwable $e) {
                // Nếu cache fail, query trực tiếp từ database (fallback)
                Log::warning('ProductController: Cache failed, querying directly', [
                    'slug' => $slug,
                    'error' => $e->getMessage(),
                ]);
                $product = Product::where('slug', $slug)
                    ->active()
                    ->select($this->productDetailSelectColumns())
                    ->first();

                if ($product) {
                    $this->ensureProductDetailRelations($product);
                }
            }

            if ($product) {
                if (! request()->hasHeader('X-In-Cache') && ! $this->hasLoadedProductDetailRelations($product)) {
                    $this->ensureProductDetailRelations($product);
                }
            } else {
                // Edge case: Cache nói là product nhưng không tìm thấy (có thể bị deactivate hoặc xóa)
                // Invalidate cache và check lại từ đầu
                Cache::forget($cacheKey ?? 'slug_type_'.$slug);
                Cache::forget('product_detail_'.$slug);

                // Check category như fallback (có thể slug này là category)
                $category = Category::where('slug', $slug)
                    ->active()
                    ->select('id', 'slug') // Chỉ select cần thiết
                    ->first();
                if ($category) {
                    // Update cache để lần sau không phải check lại
                    Cache::put($cacheKey ?? 'slug_type_'.$slug, 'category', 3600);
                    $shopController = app(ShopController::class);

                    return $shopController->index(request(), $slug);
                }

                // Không tìm thấy cả product và category
                return view('clients.pages.errors.404');
            }

            // Record product view - chuyển sang Queue để không block request
            try {
                RecordProductView::dispatch($product->id)->afterResponse();
            } catch (\Throwable $e) {
                Log::warning('ProductController: Failed to dispatch record view job', [
                    'product_id' => $product->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Vouchers với error handling
            try {
                $vouchers = Cache::remember('vouchers_for_product_'.$product->id, 3600, function () {
                    return Voucher::active()
                        ->orderBy('created_at', 'desc')
                        ->limit(4)
                        ->get();
                });
            } catch (\Throwable $e) {
                Log::warning('ProductController: Failed to load vouchers', ['error' => $e->getMessage()]);
                $vouchers = collect();
            }

            // New products với error handling
            // Sản phẩm nổi bật (featured)
            try {
                $productFeatured = Cache::remember('featured_products_sidebar', now()->addDays(2), function () {
                    $products = Product::active()
                        ->select([
                            'id', 'name', 'slug', 'price', 'sale_price',
                            'is_featured', 'created_at', 'image_ids',
                        ])
                        ->where('is_featured', true)
                        ->orderBy('name', 'desc')
                        ->orderBy('created_at', 'desc')
                        ->limit(6)
                        ->with(['flashSaleItems.flashSale']) // Eager load để badge Flash Sale không N+1
                        ->get() ?? collect();

                    // [LEVEL 5] Image Baking cho toàn bộ danh sách nổi bật
                    $products->each(fn ($p) => $p->images);

                    return $products;
                });
            } catch (\Throwable $e) {
                Log::warning('ProductController: Failed to load featured products', ['error' => $e->getMessage()]);
                $productFeatured = collect();
            }

            $cacheKey = 'related_products_'.$product->id;

            try {
                $productRelated = Cache::rememberForever($cacheKey, function () use ($product) {
                    $related = Product::getRelatedProducts($product, 12);
                    // [LEVEL 5] Image Baking cho sản phẩm liên quan
                    $related->each(fn ($p) => $p->images);

                    return $related;
                });
            } catch (\Throwable $e) {
                Log::warning('ProductController: Failed to load related products', [
                    'product_id' => $product->id,
                    'error' => $e->getMessage(),
                ]);

                $productRelated = collect();
            }

            // Sản phẩm đi kèm theo danh mục category_included_ids (nếu có)
            $includedProducts = collect();
            try {
                $includedCategoryIds = collect($product->category_included_ids ?? [])
                    ->filter(fn ($id) => ! empty($id))
                    ->unique()
                    ->values();

                if ($includedCategoryIds->isNotEmpty()) {
                    // Cache key bao gồm brand_id để cache riêng theo hãng
                    // Lưu ý: Cache key không thay đổi khi fallback, vì fallback chỉ xảy ra khi không có kết quả cùng brand
                    $brandId = $product->brand_id ?? 'no-brand';
                    $cacheKey = 'included_products_'.$product->id.'_'.$brandId.'_'.md5($includedCategoryIds->join('-'));
                    try {
                        $cachedSets = Cache::remember(
                            $cacheKey,
                            now()->addHours(6),
                            function () use ($product, $includedCategoryIds) {
                                // 1️⃣ Load tất cả categories một lần (tránh N+1)
                                $categories = Category::query()
                                    ->select('id', 'name', 'slug')
                                    ->whereIn('id', $includedCategoryIds->toArray())
                                    ->get()
                                    ->keyBy('id');

                                if ($categories->isEmpty()) {
                                    return [];
                                }

                                // 2️⃣ Tính descendants một lần cho tất cả categories (cache để tránh query lại)
                                $categoryDescendants = [];
                                $allDescendantIds = [];

                                foreach ($includedCategoryIds as $categoryId) {
                                    if (! isset($categories[$categoryId])) {
                                        continue;
                                    }
                                    // Cache descendants với key riêng cho mỗi category (cache 1 ngày)
                                    $descendantCacheKey = 'category_descendants_'.$categoryId;
                                    $descendantIds = Cache::remember($descendantCacheKey, now()->addDay(), function () use ($categoryId) {
                                        return CategoryHelper::getDescendants($categoryId);
                                    });
                                    $categoryDescendants[$categoryId] = $descendantIds;
                                    $allDescendantIds = array_merge($allDescendantIds, $descendantIds);
                                }

                                $allDescendantIds = array_values(array_unique($allDescendantIds));

                                if (empty($allDescendantIds)) {
                                    return [];
                                }

                                // 3️⃣ Query products DUY NHẤT một lần với tất cả descendant IDs
                                $limitPerCategory = 20;
                                // Lấy gấp đôi để shuffle trong PHP cho ra kết quả random mà không dùng RAND()
                                $totalLimit = count($includedCategoryIds) * $limitPerCategory * 2;

                                // Base query builder
                                $baseQuery = function ($includeBrand = true) use ($product, $allDescendantIds, $totalLimit) {
                                    return Product::query()
                                        ->active()
                                        ->where('id', '!=', $product->id)
                                        ->when($includeBrand && $product->brand_id, function ($q) use ($product) {
                                            // Lọc theo cùng hãng (brand_id) nếu có
                                            $q->where('brand_id', $product->brand_id);
                                        })
                                        ->where(function ($q) use ($allDescendantIds) {
                                            // Check primary_category_id (có thể dùng index)
                                            $q->whereIn('primary_category_id', $allDescendantIds);

                                            // Check category_ids JSON — dùng whereJsonContains thay JSON_SEARCH
                                            // Mỗi ID chỉ 1 điều kiện (bỏ duplicate string/int)
                                            foreach ($allDescendantIds as $descId) {
                                                $q->orWhereJsonContains('category_ids', (int) $descId);
                                            }
                                        })
                                        ->select([
                                            'id', 'name', 'slug', 'price', 'sale_price', 'primary_category_id',
                                            'category_ids', 'brand_id', 'is_featured', 'created_at', 'image_ids',
                                        ])
                                        ->with(['variants' => function ($q) {
                                            $q->select('id', 'product_id', 'name', 'price', 'sale_price', 'stock_quantity', 'attributes');
                                        }])
                                        // Thay inRandomOrder() (MySQL RAND() = full table scan)
                                        // bằng latest('id') rồi shuffle trong PHP — random đủ tốt, DB dùng được index
                                        ->latest('id')
                                        ->limit($totalLimit);
                                };

                                // Thử query với brand_id trước (ưu tiên cùng hãng)
                                // Shuffle trong PHP để random hoá thứ tự — tránh MySQL RAND() full scan
                                $allProducts = $baseQuery(true)->get()->shuffle();

                                // Nếu không có sản phẩm cùng brand, fallback không lọc brand
                                if ($allProducts->isEmpty() && $product->brand_id) {
                                    $allProducts = $baseQuery(false)->get()->shuffle();
                                }

                                if ($allProducts->isEmpty()) {
                                    return [];
                                }

                                // [LEVEL 5] Image Baking cho toàn bộ Included Products trước khi cache
                                $allProducts->each(fn ($p) => $p->images);

                                // 4️⃣ Group products theo category trong memory (tối ưu với collection)
                                $sets = [];

                                // Tạo map nhanh: category_id => descendant_ids để filter nhanh hơn
                                $categoryDescendantMap = [];
                                foreach ($includedCategoryIds as $categoryId) {
                                    if (isset($categories[$categoryId]) && isset($categoryDescendants[$categoryId])) {
                                        // Tạo map với cả int và string keys để đảm bảo match
                                        $descendantIds = $categoryDescendants[$categoryId];
                                        $categoryDescendantMap[$categoryId] = [];
                                        foreach ($descendantIds as $descId) {
                                            $categoryDescendantMap[$categoryId][(int) $descId] = true;
                                            $categoryDescendantMap[$categoryId][(string) $descId] = true;
                                        }
                                    }
                                }

                                // Track các sản phẩm đã được gán để tránh duplicate giữa các category
                                $assignedProductIds = [];

                                foreach ($includedCategoryIds as $categoryId) {
                                    if (! isset($categories[$categoryId]) || ! isset($categoryDescendantMap[$categoryId])) {
                                        continue;
                                    }

                                    $category = $categories[$categoryId];
                                    $descendantMap = $categoryDescendantMap[$categoryId];

                                    // Filter products thuộc category này từ tập products đã query
                                    // Loại trừ các sản phẩm đã được gán cho category trước đó
                                    $matchedProducts = $allProducts->filter(function ($p) use ($descendantMap, $assignedProductIds) {
                                        // Bỏ qua sản phẩm đã được gán
                                        if (in_array($p->id, $assignedProductIds, true)) {
                                            return false;
                                        }

                                        // Check primary_category_id (O(1) lookup) - check cả int và null
                                        if ($p->primary_category_id !== null) {
                                            if (isset($descendantMap[(int) $p->primary_category_id]) || isset($descendantMap[(string) $p->primary_category_id])) {
                                                return true;
                                            }
                                        }

                                        // Check category_ids (đã được cast thành array trong model)
                                        $productCategoryIds = $p->category_ids ?? [];
                                        if (empty($productCategoryIds) || ! is_array($productCategoryIds)) {
                                            return false;
                                        }

                                        // Check intersection với map (nhanh hơn array_intersect)
                                        foreach ($productCategoryIds as $catId) {
                                            $catIdInt = (int) $catId;
                                            $catIdStr = (string) $catId;
                                            if (isset($descendantMap[$catIdInt]) || isset($descendantMap[$catIdStr])) {
                                                return true;
                                            }
                                        }

                                        return false;
                                    })
                                        ->take($limitPerCategory)
                                        ->values();

                                    if ($matchedProducts->isNotEmpty()) {
                                        // Đánh dấu các sản phẩm đã được gán cho category này
                                        foreach ($matchedProducts as $product) {
                                            $assignedProductIds[] = $product->id;
                                        }

                                        $sets[] = [
                                            'category' => $category,
                                            'products' => $matchedProducts,
                                        ];
                                    }
                                }

                                return $sets;
                            }
                        );
                        $includedProducts = collect($cachedSets);
                    } catch (\Throwable $e) {
                        Log::warning('ProductController: Failed to load included products', [
                            'product_id' => $product->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('ProductController: Failed to process included products', [
                    'product_id' => $product->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Load comments và rating stats - gộp chung 1 Cache để giảm delay read Cache
            $comments = collect();
            $totalComments = 0;
            $ratingStats = ['average' => 0, 'count' => 0, 'distribution' => []];
            $latestReviews = collect();

            try {
                $reviewsCacheKey = "product_all_reviews_data_{$product->id}_{$product->updated_at->timestamp}";
                $reviewsData = Cache::remember($reviewsCacheKey, now()->addHours(6), function () use ($product) {
                    $comments = Comment::where('commentable_type', 'product')
                        ->where('commentable_id', $product->id)
                        ->whereNull('parent_id')
                        ->approved()
                        ->with(['account'])
                        ->orderByDesc('created_at')
                        ->limit(10)
                        ->get();

                    // Load admin replies
                    $commentIds = $comments->pluck('id');
                    if ($commentIds->isNotEmpty()) {
                        try {
                            $adminReplies = Comment::whereIn('parent_id', $commentIds)
                                ->whereNotNull('account_id')
                                ->whereHas('account', function ($q) {
                                    $q->where('role', 'admin');
                                })
                                ->with('account')
                                ->get()
                                ->keyBy('parent_id');

                            $comments->each(function ($comment) use ($adminReplies) {
                                if ($adminReplies->has($comment->id)) {
                                    $comment->setRelation('adminReply', $adminReplies->get($comment->id));
                                }
                            });
                        } catch (\Throwable $e) {
                            Log::warning('ProductController: Failed to load admin replies', ['error' => $e->getMessage()]);
                        }
                    }

                    $totalComments = Comment::where('commentable_type', 'product')
                        ->where('commentable_id', $product->id)
                        ->whereNull('parent_id')
                        ->approved()
                        ->count();

                    $ratingStatsLocal = ['average' => 0, 'count' => 0, 'distribution' => []];
                    try {
                        $commentService = app(\App\Services\CommentService::class);
                        $ratingStatsLocal = $commentService->calculateRatingStats('product', $product->id);
                    } catch (\Throwable $e) {
                        Log::warning('ProductController: Failed to calculate rating stats', ['error' => $e->getMessage()]);
                    }

                    $latestReviewsLocal = Comment::query()
                        ->where('commentable_type', 'product')
                        ->where('commentable_id', $product->id)
                        ->whereNull('parent_id')
                        ->approved()
                        ->whereNotNull('rating')
                        ->with(['account', 'adminReply.account']) // Eager load cho Schema
                        ->latest()
                        ->limit(5)
                        ->get();

                    return [
                        'comments' => $comments,
                        'totalComments' => $totalComments,
                        'ratingStats' => $ratingStatsLocal,
                        'latestReviews' => $latestReviewsLocal,
                    ];
                });

                $comments = $reviewsData['comments'];
                $totalComments = $reviewsData['totalComments'];
                $ratingStats = $reviewsData['ratingStats'];
                $latestReviews = $reviewsData['latestReviews'];
            } catch (\Throwable $e) {
                Log::warning('ProductController: Failed to load reviews data', ['error' => $e->getMessage()]);
            }
            // Tối ưu: Query trực tiếp CartItem thay vì load cả cart
            $variantCartQuantities = [];
            $productCartQuantity = 0;

            try {
                $accountId = auth('web')->id();
                $sessionId = request()->session()->getId();

                // Tìm cart ID trước
                $cartQuery = Cart::query();
                if ($accountId) {
                    $cartQuery->where('account_id', $accountId);
                } else {
                    $cartQuery->whereNull('account_id')->where('session_id', $sessionId);
                }
                $cartId = $cartQuery->latest('id')->value('id');

                if ($cartId) {
                    // Query trực tiếp items của product này trong cart
                    $items = \App\Models\CartItem::where('cart_id', $cartId)
                        ->where('product_id', $product->id)
                        ->get();

                    $productCartQuantity = (int) $items->whereNull('product_variant_id')->sum('quantity');

                    foreach ($items as $item) {
                        if ($item->product_variant_id) {
                            $variantCartQuantities[$item->product_variant_id] = ($variantCartQuantities[$item->product_variant_id] ?? 0) + (int) $item->quantity;
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('ProductController: Failed to resolve cart quantities for stock display', [
                    'product_id' => $product->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // quantityProductDetail: tồn kho sản phẩm (không variant) sau khi trừ trong giỏ
            if ($product->hasVariants()) {
                $quantityProductDetail = 0;
            } else {
                $baseStock = $product->stock_quantity ?? 0;
                $remaining = max(0, (int) $baseStock - (int) $productCartQuantity);
                $quantityProductDetail = $remaining;
            }

            // CSKH dynamic (cache 1 ngày)
            $supportStaff = Cache::remember('support_staff_active', now()->addDay(), function () {
                return SupportStaff::where('is_active', true)->orderBy('sort_order')->get();
            });

            // [CODE-FIRST OPTIMIZATION] Di dời toàn bộ logic từ View sang Controller
            // 1. Tính toán Breadcrumb Path (loại bỏ while loop trong Blade)
            $breadcrumbPath = collect();
            $curr = $product->relationLoaded('primaryCategory') ? $product->primaryCategory : null;
            while ($curr) {
                $breadcrumbPath->prepend($curr);
                $curr = $curr->parent;
            }

            // 2. Xác định Wizard Category (loại bỏ direct query trong Blade)
            $wizardCategoryId = null;
            if ($product->primaryCategory) {
                $primaryCat = $product->primaryCategory;
                if ($primaryCat->parent_id === null) {
                    $wizardCategoryId = $primaryCat->id;
                } else {
                    $parent = $primaryCat->parent;
                    while ($parent && $parent->parent_id !== null) {
                        $parent = $parent->parent;
                    }
                    $wizardCategoryId = $parent ? $parent->id : $primaryCat->id;
                }
            }
            if (! $wizardCategoryId) {
                $wizardCategoryId = Cache::remember('default_wizard_cat', 86400, function () {
                    return Category::active()->whereNull('parent_id')->orderBy('order')->orderBy('name')->value('id');
                });
            }

            // 3. Tính toán Flash Sale đang diễn ra
            $now = now();
            $activeFlashSaleItem = $product->flashSaleItems
                ->where('is_active', 1)
                ->first(function ($item) use ($now) {
                    $fs = $item->relationLoaded('flashSale') ? $item->flashSale : null;

                    return $fs
                        && $fs->is_active
                        && ($fs->status ?? '') === 'active'
                        && $fs->start_time <= $now
                        && $fs->end_time >= $now;
                });
            $activeFlashSale = $activeFlashSaleItem?->flashSale;

            // 4. Asset Versioning (loại bỏ filemtime Disk I/O)
            $v = config('app.asset_version', '1.0.8');

            // 5. Popup content (active & trong khung thời gian)
            $popup = PopupContent::active()->orderBy('sort_order')->first();

            // 6. Schema Keywords & Meta logic (Code-First Speed)
            $schemaKeywords = $product->meta_keywords;
            if (is_string($schemaKeywords)) {
                $schemaKeywords = array_map('trim', explode(',', $schemaKeywords));
            }
            if (! is_array($schemaKeywords) || empty($schemaKeywords)) {
                $schemaKeywords = [
                    'cảm biến công nghiệp', 'PLC', 'HMI', 'biến tần', 'servo',
                    'encoder', 'rơ le', 'thiết bị tự động hóa', 'tự động hóa công nghiệp',
                    'AutoSensor Việt Nam',
                ];
            }
            $schemaKeywords = array_filter($schemaKeywords, function ($k) {
                $k = trim($k);

                return ! empty($k) && mb_strlen($k) <= 50 && strpos($k, ':') === false;
            });
            if ($product->slug && mb_strlen($product->slug) <= 50) {
                $schemaKeywords[] = $product->slug;
            }
            $schemaKeywords = array_values(array_unique($schemaKeywords));

            $productView = $this->buildProductViewData(
                $product,
                $variantCartQuantities,
                (int) $quantityProductDetail,
                $activeFlashSaleItem
            );
            $reviewDisplay = $this->buildReviewDisplayData($ratingStats);
            $tagLinks = $this->buildTagLinks($product);
            $catalogLinks = $this->normalizeCatalogLinks($product->link_catalog ?? []);
            $videoEmbedUrl = $this->normalizeVideoEmbedUrl($product->video_url ?? null);
            $featuredProductCards = $this->mapFeaturedProductsForView($productFeatured);
            $relatedProductCards = $this->mapRelatedProductsForView($productRelated);
            $includedAccessorySets = $this->mapIncludedProductSetsForView($includedProducts);
            $currentFlashSaleDetails = $this->buildCurrentFlashSaleDetails($activeFlashSale);

            return view('clients.pages.single.index',
                compact(
                    'product',
                    'vouchers',
                    'productFeatured',
                    'productRelated',
                    'includedProducts',
                    'quantityProductDetail',
                    'comments',
                    'ratingStats',
                    'latestReviews',
                    'totalComments',
                    'variantCartQuantities',
                    'productCartQuantity',
                    'supportStaff',
                    'popup',
                    'breadcrumbPath',
                    'wizardCategoryId',
                    'activeFlashSaleItem',
                    'activeFlashSale',
                    'v',
                    'schemaKeywords',
                    'productView',
                    'reviewDisplay',
                    'tagLinks',
                    'catalogLinks',
                    'videoEmbedUrl',
                    'featuredProductCards',
                    'relatedProductCards',
                    'includedAccessorySets',
                    'currentFlashSaleDetails'
                )
            );
        } catch (\Throwable $e) {
            Log::error('ProductController: Fatal error in detail method', [
                'slug' => $slug,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Nếu có lỗi nghiêm trọng, trả về 404 thay vì 500 để tránh ảnh hưởng SEO
            return view('clients.pages.errors.404');
        }
    }

    /**
     * Preload tags để tránh N+1 query trong view
     */
    private function preloadTags(Product $product): void
    {
        $tagIds = $product->tag_ids ?? [];
        if (empty($tagIds)) {
            return;
        }

        $ids = is_array($tagIds) ? $tagIds : json_decode($tagIds, true) ?? [];
        if (empty($ids)) {
            return;
        }

        // Cache tags 30 ngày theo product để tránh query lặp lại
        $cacheKey = 'product_tags_'.$product->id.'_'.md5(json_encode($ids));
        $tags = Cache::remember(
            $cacheKey,
            now()->addDays(30),
            function () use ($ids) {
                return Tag::whereIn('id', $ids)
                    ->where('is_active', true)
                    ->get();
            }
        );

        // Set relation để accessor không query lại
        $product->setRelation('tags', $tags);
    }

    private function productDetailSelectColumns(): array
    {
        return [
            'id',
            'name',
            'slug',
            'sku',
            'price',
            'sale_price',
            'stock_quantity',
            'primary_category_id',
            'brand_id',
            'is_active',
            'image_ids',
            'description',
            'short_description',
            'meta_title',
            'meta_description',
            'meta_keywords',
            'category_ids',
            'tag_ids',
            'link_catalog',
            'video_url',
            'category_included_ids',
            'is_featured',
            'created_at',
            'updated_at',
        ];
    }

    private function productDetailRelations(): array
    {
        return [
            'variants:id,product_id,sku,name,price,sale_price,stock_quantity,attributes,is_active',
            'brand:id,name,slug',
            'primaryCategory:id,name,slug,parent_id',
            'primaryCategory.parent:id,name,slug,parent_id',
            'primaryCategory.parent.parent:id,name,slug,parent_id',
            'primaryCategory.parent.parent.parent:id,name,slug,parent_id',
            'flashSaleItems:id,product_id,flash_sale_id,original_price,sale_price,stock,sold,max_per_user,is_active',
            'flashSaleItems.flashSale:id,title,status,is_active,start_time,end_time',
            'faqs:id,product_id,question,answer',
            'howTos:id,product_id,title,description,steps,supplies,is_active',
        ];
    }

    private function hasLoadedProductDetailRelations(Product $product): bool
    {
        return $product->relationLoaded('variants')
            && $product->relationLoaded('brand')
            && $product->relationLoaded('primaryCategory')
            && $product->relationLoaded('flashSaleItems')
            && $product->relationLoaded('faqs')
            && $product->relationLoaded('howTos')
            && $product->relationLoaded('tags');
    }

    private function ensureProductDetailRelations(Product $product): void
    {
        $product->loadMissing($this->productDetailRelations());
        Product::preloadImages([$product]);
        $this->preloadTags($product);
        $product->images;
    }

    private function buildProductViewData(
        Product $product,
        array $variantCartQuantities,
        int $quantityProductDetail,
        mixed $activeFlashSaleItem
    ): array {
        $variants = $product->variants ?? collect();
        $hasVariants = $variants->isNotEmpty();
        $variantCartQuantities = collect($variantCartQuantities);
        $firstVariant = $variants->first();

        if ($hasVariants && $firstVariant) {
            $original = (float) ($firstVariant->price ?? 0);
            $sale = $this->hasValidSalePrice($original, $firstVariant->sale_price)
                ? (float) $firstVariant->sale_price
                : null;
            $availableStock = $this->resolveRemainingStock(
                $firstVariant->stock_quantity,
                (int) $variantCartQuantities->get($firstVariant->id, 0)
            );
            $isOutOfStock = $availableStock !== null && $availableStock <= 0;
        } else {
            $priceSource = $activeFlashSaleItem ?? $product;
            $original = (float) ($priceSource->original_price ?? $priceSource->price ?? 0);
            $sale = $this->hasValidSalePrice($original, $priceSource->sale_price ?? null)
                ? (float) $priceSource->sale_price
                : null;
            $availableStock = max(0, $quantityProductDetail);
            $isOutOfStock = $availableStock <= 0;
        }

        $primaryImageUrl = $product->primaryImage?->url ?? null;
        $galleryImages = ($product->images ?? collect())->values()->map(function ($image, int $index) use ($primaryImageUrl) {
            $isActive = false;

            if ($primaryImageUrl && $image->url === $primaryImageUrl) {
                $isActive = true;
            } elseif (! $primaryImageUrl && $index === 0) {
                $isActive = true;
            }

            return [
                'url' => $image->url ?? 'no-image.webp',
                'active' => $isActive,
                'alt' => $image->alt,
                'title' => $image->title,
            ];
        })->all();

        if (! empty($galleryImages) && ! collect($galleryImages)->contains(fn ($image) => ! empty($image['active']))) {
            $galleryImages[0]['active'] = true;
        }

        $overlayImages = (($product->images ?? collect())->isNotEmpty()
            ? $product->images
            : ($product->primaryImage ? collect([$product->primaryImage]) : collect()))
            ->values()
            ->map(fn ($image) => [
                'url' => $image->url ?? 'no-image.webp',
                'alt' => $image->alt,
            ])
            ->all();

        $variantOptions = $variants->values()->map(function ($variant) use ($variantCartQuantities) {
            $attributes = is_array($variant->attributes)
                ? $variant->attributes
                : (is_string($variant->attributes) ? json_decode($variant->attributes, true) : []);
            $remainingStock = $this->resolveRemainingStock(
                $variant->stock_quantity,
                (int) $variantCartQuantities->get($variant->id, 0)
            );

            return [
                'id' => $variant->id,
                'sku' => $variant->sku ?? 'AutoSensor',
                'name' => $variant->name,
                'price' => (float) ($variant->price ?? 0),
                'sale_price' => $variant->sale_price !== null ? (float) $variant->sale_price : null,
                'display_price' => (float) $variant->display_price,
                'discount_percent' => $variant->discount_percent,
                'remaining_stock' => $remainingStock,
                'is_out_of_stock' => $remainingStock !== null && $remainingStock <= 0,
                'attributes' => is_array($attributes) ? $attributes : [],
            ];
        })->all();

        return [
            'primary_image_url' => $this->productImageUrl($primaryImageUrl),
            'primary_image_mobile_url' => $this->productImageUrl($primaryImageUrl, 'resize/500x500'),
            'gallery_images' => $galleryImages,
            'overlay_images' => $overlayImages,
            'has_variants' => $hasVariants,
            'selected_variant_id' => $firstVariant?->id,
            'first_variant_label' => $firstVariant?->sku ?? 'AutoSensor',
            'variants' => $variantOptions,
            'original_price' => $original,
            'sale_price' => $sale,
            'discount_percent' => $this->calculateDiscountPercent($original, $sale),
            'available_stock' => $availableStock,
            'is_out_of_stock' => $isOutOfStock,
        ];
    }

    private function buildReviewDisplayData(array $ratingStats): array
    {
        $average = (float) ($ratingStats['average_rating'] ?? 0);
        $realCount = (int) ($ratingStats['total_comments'] ?? 0);
        $hasRealReviews = $realCount > 0 && $average > 0;

        return [
            'average' => $average,
            'has_real_reviews' => $hasRealReviews,
            'star_count' => $hasRealReviews ? max(1, min(5, (int) round($average))) : rand(4, 5),
            'display_count' => $realCount > 0 ? $realCount : rand(10, 1000),
        ];
    }

    private function buildTagLinks(Product $product): array
    {
        return collect($product->tags ?? [])
            ->filter(fn ($tag) => ! empty($tag->slug) && ! empty($tag->name))
            ->map(fn ($tag) => [
                'name' => $tag->name,
                'slug' => $tag->slug,
            ])
            ->values()
            ->all();
    }

    private function normalizeCatalogLinks(mixed $catalogs): array
    {
        if (! is_array($catalogs)) {
            $catalogs = [];
        }

        return collect($catalogs)
            ->filter()
            ->values()
            ->map(function ($catalog, int $index) {
                $fileName = basename($catalog);

                return [
                    'label' => $fileName ?: 'Catalog '.($index + 1),
                    'url' => $this->assetUrl($catalog),
                ];
            })
            ->all();
    }

    private function normalizeVideoEmbedUrl(?string $videoUrl): ?string
    {
        if (! $videoUrl) {
            return null;
        }

        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $videoUrl, $matches)) {
            return 'https://www.youtube.com/embed/'.$matches[1];
        }

        if (preg_match('/vimeo\.com\/(\d+)/', $videoUrl, $matches)) {
            return 'https://player.vimeo.com/video/'.$matches[1];
        }

        return $videoUrl;
    }

    private function mapFeaturedProductsForView(Collection $products): array
    {
        return $products->take(10)->values()->map(function (Product $product) {
            $badge = [
                'label' => '⭐ Hot',
                'style' => 'background: #ffd700;',
            ];

            if ($this->isLoadedFlashSaleActive($product)) {
                $badge = [
                    'label' => '⚡ Sale',
                    'style' => 'background: #ff4444;',
                ];
            } elseif ($this->hasValidSalePrice($product->price, $product->sale_price)) {
                $badge = [
                    'label' => '-'.$this->calculateDiscountPercent((float) $product->price, (float) $product->sale_price).'%',
                    'style' => 'background: #ff6b35;',
                ];
            }

            return [
                'slug' => $product->slug,
                'name' => $product->name,
                'image_url' => $product->primaryImage?->url ?? 'no-image.webp',
                'formatted_price' => $this->formatPrice((float) ($product->sale_price ?? $product->price ?? 0)),
                'badge' => $badge,
            ];
        })->all();
    }

    private function mapRelatedProductsForView(Collection $products): array
    {
        return $products->values()->map(function (Product $product) {
            $badge = null;

            if ($product->is_featured) {
                $badge = 'Hot';
            } elseif ($product->created_at && $product->created_at->diffInDays(now()) <= 30) {
                $badge = 'New';
            }

            return [
                'slug' => $product->slug,
                'name' => $product->name,
                'image_url' => $product->primaryImage?->url ?? 'no-image.webp',
                'formatted_price' => $this->formatPrice((float) ($product->sale_price ?? $product->price ?? 0)),
                'badge' => $badge,
            ];
        })->all();
    }

    private function mapIncludedProductSetsForView(Collection $sets): array
    {
        return $sets->values()->map(function (array $set) {
            $accessories = collect($set['products'] ?? [])->values()->map(function (Product $product) {
                $variants = ($product->variants ?? collect())->values()->map(function ($variant) {
                    $attributes = is_array($variant->attributes)
                        ? $variant->attributes
                        : (is_string($variant->attributes) ? json_decode($variant->attributes, true) : []);

                    return [
                        'id' => $variant->id,
                        'name' => $variant->name,
                        'price' => (float) ($variant->price ?? 0),
                        'sale_price' => $variant->sale_price !== null ? (float) $variant->sale_price : null,
                        'display_price' => (float) $variant->display_price,
                        'stock_quantity' => $variant->stock_quantity,
                        'attributes' => is_array($attributes) ? $attributes : [],
                    ];
                })->all();

                return [
                    'id' => $product->id,
                    'slug' => $product->slug,
                    'name' => $product->name,
                    'image_url' => $product->primaryImage?->url ?? 'no-image.webp',
                    'price' => (float) ($product->price ?? 0),
                    'sale_price' => $product->sale_price !== null ? (float) $product->sale_price : null,
                    'formatted_price' => $this->formatPrice((float) ($product->sale_price ?? $product->price ?? 0)),
                    'has_variants' => ! empty($variants),
                    'variants' => $variants,
                ];
            })->all();

            return [
                'category_name' => $set['category']->name ?? 'Danh mục khác',
                'products' => $accessories,
            ];
        })->all();
    }

    private function buildCurrentFlashSaleDetails(mixed $activeFlashSale): ?array
    {
        if (! $activeFlashSale) {
            return null;
        }

        return [
            'title' => $activeFlashSale->title,
            'start_time' => optional($activeFlashSale->start_time)->format('H:i'),
            'end_time' => optional($activeFlashSale->end_time)->format('H:i'),
            'date' => optional($activeFlashSale->start_time)->format('d/m'),
        ];
    }

    private function isLoadedFlashSaleActive(Product $product): bool
    {
        if (! $product->relationLoaded('flashSaleItems')) {
            return false;
        }

        $now = now();

        return $product->flashSaleItems
            ->where('is_active', 1)
            ->contains(function ($item) use ($now) {
                $flashSale = $item->relationLoaded('flashSale') ? $item->flashSale : null;

                return $flashSale
                    && $flashSale->is_active
                    && ($flashSale->status ?? '') === 'active'
                    && $flashSale->start_time <= $now
                    && $flashSale->end_time >= $now;
            });
    }

    private function hasValidSalePrice(mixed $original, mixed $sale): bool
    {
        return $sale !== null
            && (float) $sale > 0
            && (float) $original > 0
            && (float) $sale < (float) $original;
    }

    private function calculateDiscountPercent(?float $original, ?float $sale): ?int
    {
        if (! $this->hasValidSalePrice($original, $sale)) {
            return null;
        }

        return (int) round((($original - $sale) / $original) * 100);
    }

    private function resolveRemainingStock(mixed $stockQuantity, int $inCart): ?int
    {
        if ($stockQuantity === null || $stockQuantity === '') {
            return null;
        }

        return max(0, (int) $stockQuantity - $inCart);
    }

    private function formatPrice(float $price): string
    {
        return number_format($price, 0, ',', '.').'đ';
    }

    private function productImageUrl(?string $filename, ?string $prefix = null): string
    {
        $filename = $filename ?: 'no-image.webp';
        $path = 'clients/assets/img/clothes/';

        if ($prefix) {
            $path .= trim($prefix, '/').'/';
        }

        return asset($path.$filename);
    }

    private function assetUrl(?string $path): string
    {
        if (! $path) {
            return '#';
        }

        return filter_var($path, FILTER_VALIDATE_URL) ? $path : asset($path);
    }

    public function wishlist(Request $request)
    {
        $productID = $request->input('product_id');
        $query = Favorite::where('product_id', $productID);

        if (auth('web')->check()) {
            // user đăng nhập
            $query->where('account_id', auth('web')->id());
        } else {
            // user khách dùng session
            $query->where('session_id', session()->getId());
        }

        $wishlist = $query->first();

        if ($wishlist) {
            return redirect()->back()->with('error', 'Sản phẩm đã có trong danh sách yêu thích.');
        }
        $product = Product::where('id', $productID)->active()->first();

        if (! $product) {
            return redirect()->back()->with('error', 'Sản phẩm không tồn tại.');
        }

        try {
            $accountID = auth('web')->user()->id ?? null;
            if ($accountID) {
                Favorite::firstOrCreate([
                    'account_id' => $accountID,
                    'product_id' => $productID,
                    'session_id' => null,
                ]);
            } else {
                Favorite::firstOrCreate([
                    'account_id' => null,
                    'product_id' => $productID,
                    'session_id' => session()->getId(),
                ]);
            }

            return redirect()->back()->with('success', 'Thêm vào danh sách yêu thích thành công.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Đã xảy ra lỗi khi thêm vào danh sách yêu thích.');
        }
    }

    public function wishlistRemove(Request $request)
    {
        $productID = $request->input('product_id');

        // Nếu user đăng nhập
        $accountID = auth('web')->user()->id ?? null;

        // Query chung
        $query = Favorite::where('product_id', $productID);

        if ($accountID) {
            $query->where('account_id', $accountID);
        } else {
            $query->where('session_id', session()->getId());
        }

        // Lấy bản ghi
        $favorite = $query->first();

        if (! $favorite) {
            $request->merge(['product_id' => $productID]);

            return $this->wishlist($request);
        }

        try {
            $favorite->delete();

            return redirect()->back()->with('success', 'Đã xóa khỏi danh sách yêu thích.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Không thể xóa sản phẩm này.');
        }
    }

    /**
     * Nhận yêu cầu gọi tư vấn từ trang chi tiết sản phẩm.
     */
    public function phoneRequest(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'phone' => ['required', 'regex:/^[0-9]{10,11}$/'],
        ], [
            'product_id.required' => 'Thiếu mã sản phẩm.',
            'product_id.exists' => 'Sản phẩm không tồn tại.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại không hợp lệ (10-11 chữ số).',
        ]);

        return redirect()->back()->with('success', 'Đã nhận số điện thoại, chúng tôi sẽ liên hệ sớm.');
    }

    /**
     * Xử lý form tư vấn nhanh từ popup thông minh
     */
    public function quickConsultation(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('Quick Consultation Request Received', $request->all());

        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'name' => ['nullable', 'string', 'max:100'],
            'phone' => ['required', 'string', 'min:10', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'message' => ['nullable', 'string', 'max:500'],
            'trigger_type' => ['required', 'string', 'in:view_time,multiple_products,manual'],
            'behavior_data' => ['nullable', 'array'],
        ], [
            'product_id.required' => 'Thiếu mã sản phẩm.',
            'product_id.exists' => 'Sản phẩm không tồn tại.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'email.email' => 'Email không hợp lệ.',
            'trigger_type.required' => 'Thiếu thông tin trigger.',
        ]);

        $product = Product::with('brand')->findOrFail($validated['product_id']);
        $categoryIds = $product->category_ids ?? [];
        $brand = $product->brand;

        $lead = \App\Models\QuickConsultationLead::create([
            'product_id' => $validated['product_id'],
            'name' => isset($validated['name']) ? strip_tags($validated['name']) : null,
            'phone' => strip_tags($validated['phone']),
            'email' => $validated['email'] ?? null,
            'message' => isset($validated['message']) ? strip_tags($validated['message']) : null,
            'trigger_type' => $validated['trigger_type'],
            'behavior_data' => array_merge($validated['behavior_data'] ?? [], [
                'category_ids' => $categoryIds,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'brand_name' => $brand ? $brand->name : null,
                'brand_slug' => $brand ? $brand->slug : null,
            ]),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => $request->input('session_id'),
        ]);

        // Gửi email thông báo cho quản trị viên
        try {
            \Illuminate\Support\Facades\Log::info('Attempting to send Admin Mail...');
            \Illuminate\Support\Facades\Mail::to('admin@autosensor.vn')
                ->send(new \App\Mail\QuickConsultationMail($lead));
            \Illuminate\Support\Facades\Log::info('Admin Mail Sent successfully.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Quick Consultation Admin Mail Error: '.$e->getMessage());
        }

        // Gửi email xác nhận cho khách hàng (nếu có email)
        if ($lead->email) {
            try {
                \Illuminate\Support\Facades\Log::info('Attempting to send Customer Mail to: '.$lead->email);
                \Illuminate\Support\Facades\Mail::to($lead->email)
                    ->send(new \App\Mail\CustomerConsultationMail($lead, $product, $brand));
                \Illuminate\Support\Facades\Log::info('Customer Mail Sent successfully.');
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Quick Consultation Customer Mail Error: '.$e->getMessage());
            }
        } else {
            \Illuminate\Support\Facades\Log::info('Skipping Customer Mail: No email provided.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Cảm ơn bạn đã liên hệ! Chúng tôi sẽ gọi lại cho bạn trong thời gian sớm nhất.',
        ]);
    }
}
