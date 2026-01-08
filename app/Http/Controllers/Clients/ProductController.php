<?php

namespace App\Http\Controllers\Clients;

use App\Helpers\CategoryHelper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Clients\ShopController;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Favorite;
use App\Models\Cart;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductSlugHistory;
use App\Models\PopupContent;
use App\Models\SupportStaff;
use App\Models\Voucher;
use App\Services\ProductViewService;
use Illuminate\Http\Request;
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
                        ->with([
                            'variants', 
                            'brand',
                            'primaryCategory.parent' // Eager load parent để breadcrumb không N+1
                        ]) // Eager load để tránh N+1 query
                        ->first();

                    if ($product) {
                        Product::preloadImages([$product]);
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
                    ->with([
                        'variants', 
                        'brand',
                        'primaryCategory.parent' // Eager load parent để breadcrumb không N+1
                    ])
                    ->first();
                
                if ($product) {
                    Product::preloadImages([$product]);
                }
            }

            if ($product) {
                Product::preloadImages([$product]);
                // Load variants nếu chưa có
                if (! $product->relationLoaded('variants')) {
                    $product->load('variants');
                }
            } else {
                // Edge case: Cache nói là product nhưng không tìm thấy (có thể bị deactivate hoặc xóa)
                // Invalidate cache và check lại từ đầu
                Cache::forget($cacheKey);
                Cache::forget('product_detail_'.$slug);
                
                // Check category như fallback (có thể slug này là category)
                $category = Category::where('slug', $slug)
                    ->active()
                    ->select('id', 'slug') // Chỉ select cần thiết
                    ->first();
                if ($category) {
                    // Update cache để lần sau không phải check lại
                    Cache::put($cacheKey, 'category', 3600);
                    $shopController = app(ShopController::class);
                    return $shopController->index(request(), $slug);
                }
                
                // Không tìm thấy cả product và category
                return view('clients.pages.errors.404');
            }

            // Record product view - không block nếu fail
            try {
                $this->productViewService->recordView($product);
            } catch (\Throwable $e) {
                Log::warning('ProductController: Failed to record view', [
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
            try {
                $productNew = Cache::remember('new_products', now()->addDays(value: 7), function () use ($product) {
                    $products = Product::active()
                        ->where('id', '!=', $product->id)
                        ->orderBy('created_at', 'desc')
                        ->inRandomOrder()
                        ->limit(10)
                        ->withApprovedCommentsMeta()
                        ->get() ?? collect();

                    Product::preloadImages($products);

                    return $products;
                });
                Product::preloadImages($productNew);
            } catch (\Throwable $e) {
                Log::warning('ProductController: Failed to load new products', ['error' => $e->getMessage()]);
                $productNew = collect();
            }

            $cacheKey = 'related_products_' . $product->id;

            try {
                $productRelated = Cache::rememberForever($cacheKey, fn () =>
                    Product::getRelatedProducts($product, 12)
                );
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
                    $cacheKey = 'included_products_'.$product->id.'_'.md5($includedCategoryIds->join('-'));
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

                                // 2️⃣ Tính descendants một lần cho tất cả categories (có thể cache trong helper)
                                $categoryDescendants = [];
                                $allDescendantIds = [];
                                
                                foreach ($includedCategoryIds as $categoryId) {
                                    if (!isset($categories[$categoryId])) {
                                        continue;
                                    }
                                    // Cache descendants trong memory để tránh query lại
                                    $descendantIds = CategoryHelper::getDescendants($categoryId);
                                    $categoryDescendants[$categoryId] = $descendantIds;
                                    $allDescendantIds = array_merge($allDescendantIds, $descendantIds);
                                }

                                $allDescendantIds = array_values(array_unique($allDescendantIds));

                                if (empty($allDescendantIds)) {
                                    return [];
                                }

                                // 3️⃣ Query products DUY NHẤT một lần với tất cả descendant IDs
                                // Lấy nhiều hơn để có thể group theo category sau
                                $limitPerCategory = 3;
                                $totalLimit = count($includedCategoryIds) * $limitPerCategory * 2; // Lấy dư để có thể group
                                
                                $allProducts = Product::query()
                                    ->active()
                                    ->where('id', '!=', $product->id)
                                    ->where(function ($q) use ($allDescendantIds) {
                                        $q->whereIn('primary_category_id', $allDescendantIds)
                                            ->orWhere(function ($sub) use ($allDescendantIds) {
                                                foreach ($allDescendantIds as $id) {
                                                    $sub->orWhereJsonContains('category_ids', (int) $id)
                                                        ->orWhereJsonContains('category_ids', (string) $id);
                                                }
                                            });
                                    })
                                    ->with('variants')
                                    ->inRandomOrder()
                                    ->limit($totalLimit)
                                    ->get();

                                if ($allProducts->isEmpty()) {
                                    return [];
                                }

                                // Preload images một lần cho tất cả products
                                Product::preloadImages($allProducts);

                                // 4️⃣ Group products theo category trong memory (GIỮ NGUYÊN LOGIC)
                                $sets = [];
                                
                                foreach ($includedCategoryIds as $categoryId) {
                                    if (!isset($categories[$categoryId])) {
                                        continue;
                                    }
                                    
                                    $category = $categories[$categoryId];
                                    $descendantIds = $categoryDescendants[$categoryId] ?? [];

                                    if (empty($descendantIds)) {
                                        continue;
                                    }

                                    // Filter products thuộc category này từ tập products đã query
                                    $matchedProducts = $allProducts->filter(function ($p) use ($descendantIds) {
                                        // Check primary_category_id
                                        if (in_array($p->primary_category_id, $descendantIds, true)) {
                                            return true;
                                        }
                                        // Check category_ids JSON
                                        $productCategoryIds = collect($p->category_ids ?? [])
                                            ->map(fn ($v) => (int) $v)
                                            ->toArray();
                                        return !empty(array_intersect($productCategoryIds, $descendantIds));
                                    })
                                    ->take($limitPerCategory) // Limit 3 như logic cũ
                                    ->values();

                                    if ($matchedProducts->isNotEmpty()) {
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

            // Load comments và rating stats - chỉ load 10 đầu tiên
            // Tối ưu: Cache comments và rating stats để giảm query
            $comments = collect();
            $totalComments = 0;
            $ratingStats = ['average' => 0, 'count' => 0, 'distribution' => []];
            $latestReviews = collect();
            
            try {
                // Cache comments với key dựa trên product ID và updated_at để invalidate khi product update
                $commentsCacheKey = "product_comments_{$product->id}_{$product->updated_at->timestamp}";
                $comments = Cache::remember($commentsCacheKey, now()->addHours(6), function () use ($product) {
                    $comments = Comment::where('commentable_type', 'product')
                        ->where('commentable_id', $product->id)
                        ->whereNull('parent_id')
                        ->approved()
                        ->with(['account'])
                        ->orderByDesc('created_at')
                        ->limit(10)
                        ->get();

                    // Load admin replies separately để đảm bảo relationship hoạt động đúng
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

                            // Attach admin replies to comments
                            $comments->each(function ($comment) use ($adminReplies) {
                                if ($adminReplies->has($comment->id)) {
                                    $comment->setRelation('adminReply', $adminReplies->get($comment->id));
                                }
                            });
                        } catch (\Throwable $e) {
                            Log::warning('ProductController: Failed to load admin replies', ['error' => $e->getMessage()]);
                        }
                    }

                    return $comments;
                });

                // Cache total comments count
                $totalCommentsCacheKey = "product_comments_count_{$product->id}_{$product->updated_at->timestamp}";
                $totalComments = Cache::remember($totalCommentsCacheKey, now()->addHours(6), function () use ($product) {
                    return Comment::where('commentable_type', 'product')
                        ->where('commentable_id', $product->id)
                        ->whereNull('parent_id')
                        ->approved()
                        ->count();
                });

                // Cache rating stats
                $ratingStatsCacheKey = "product_rating_stats_{$product->id}_{$product->updated_at->timestamp}";
                try {
                    $ratingStats = Cache::remember($ratingStatsCacheKey, now()->addHours(6), function () use ($product) {
                        $commentService = app(\App\Services\CommentService::class);
                        return $commentService->calculateRatingStats('product', $product->id);
                    });
                } catch (\Throwable $e) {
                    Log::warning('ProductController: Failed to calculate rating stats', ['error' => $e->getMessage()]);
                }

                // 5 đánh giá mới nhất cho schema Product
                $cacheKey = "product_latest_reviews_{$product->id}_{$product->updated_at->timestamp}";

                $latestReviews = Cache::rememberForever($cacheKey, fn () =>
                    Comment::query()
                        ->where('commentable_type', 'product')
                        ->where('commentable_id', $product->id)
                        ->whereNull('parent_id')
                        ->approved()
                        ->whereNotNull('rating')
                        ->latest()
                        ->limit(5)
                        ->get()
                );
            } catch (\Throwable $e) {
                Log::warning('ProductController: Failed to load comments', [
                    'product_id' => $product->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Tính tồn kho còn lại sau khi trừ số lượng trong giỏ hàng hiện tại
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

            // Popup content (active & trong khung thời gian) - không cache theo yêu cầu
            $popup = PopupContent::active()->orderBy('sort_order')->first();
            
            return view('clients.pages.single.index',
                compact(
                    'product',
                    'vouchers',
                    'productNew',
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
                    'popup'
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
}
