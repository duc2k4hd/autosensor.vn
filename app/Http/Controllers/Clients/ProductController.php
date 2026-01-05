<?php

namespace App\Http\Controllers\Clients;

use App\Helpers\CategoryHelper;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Favorite;
use App\Models\Product;
use App\Models\ProductSlugHistory;
use App\Models\Voucher;
use App\Services\ProductViewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function __construct(
        private ProductViewService $productViewService
    ) {}

    public function detail($slug)
    {
        try {
            $quantityProductDetail = Product::where('slug', $slug)
                ->active()
                ->value('stock_quantity') ?? 0;
            
            // Sử dụng try-catch cho cache để tránh lỗi nếu cache driver fail
            try {
                $product = Cache::rememberForever('product_detail_'.$slug, function () use ($slug) {
                    $product = Product::where('slug', $slug)
                        ->active()
                        ->with('variants')
                        ->first();

                    if ($product) {
                        Product::preloadImages([$product]);
                    }

                    return $product;
                });
            } catch (\Throwable $e) {
                // Nếu cache fail, query trực tiếp từ database
                Log::warning('ProductController: Cache failed, querying directly', [
                    'slug' => $slug,
                    'error' => $e->getMessage(),
                ]);
                $product = Product::where('slug', $slug)
                    ->active()
                    ->with('variants')
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
            }

            if (! $product) {
                $history = ProductSlugHistory::where('slug', $slug)->first();
                if ($history) {
                    $newProduct = Product::active()->find($history->product_id);
                    if ($newProduct) {
                        // Redirect 301 to new slug
                        return redirect()->route('client.product.detail', $newProduct->slug, 301);
                    }
                }

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
                $productNew = Cache::remember('new_products', 3600, function () use ($product) {
                    $products = Product::active()
                        ->where('id', '!=', $product->id)
                        ->orderBy('created_at', 'desc')
                        ->inRandomOrder()
                        ->limit(9)
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

            // Sản phẩm liên quan: lấy 5 sản phẩm trước và 5 sản phẩm sau, cache vĩnh viễn
            try {
                $productRelated = Cache::rememberForever('related_products_'.$product->id, function () use ($product) {
                    // Hàm getRelatedProducts đã tự preload images
                    return Product::getRelatedProducts($product, 10);
                });
            } catch (\Throwable $e) {
                Log::warning('ProductController: Failed to load related products', [
                    'product_id' => $product->id,
                    'error' => $e->getMessage(),
                ]);
                // Fallback: query trực tiếp không cache
                try {
                    $productRelated = Product::getRelatedProducts($product, 10);
                } catch (\Throwable $e2) {
                    Log::error('ProductController: Failed to load related products (fallback)', [
                        'product_id' => $product->id,
                        'error' => $e2->getMessage(),
                    ]);
                    $productRelated = collect();
                }
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
                                $sets = [];

                                foreach ($includedCategoryIds as $categoryId) {
                                    $category = Category::select('id', 'name', 'slug')->find($categoryId);
                                    if (! $category) {
                                        continue;
                                    }

                                    $descendantIds = CategoryHelper::getDescendants($categoryId);

                                    $products = Product::query()
                                        ->active()
                                        ->where('id', '!=', $product->id)
                                        ->where(function ($q) use ($descendantIds) {
                                            $q->whereIn('primary_category_id', $descendantIds)
                                                ->orWhere(function ($sub) use ($descendantIds) {
                                                    foreach ($descendantIds as $id) {
                                                        $sub->orWhereJsonContains('category_ids', (int) $id)
                                                            ->orWhereJsonContains('category_ids', (string) $id);
                                                    }
                                                });
                                        })
                                        ->with('variants')
                                        ->inRandomOrder()
                                        ->limit(3)
                                        ->get();

                                    if ($products->isEmpty()) {
                                        continue;
                                    }

                                    Product::preloadImages($products);

                                    $sets[] = [
                                        'category' => $category,
                                        'products' => $products,
                                    ];
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
            $comments = collect();
            $totalComments = 0;
            $ratingStats = ['average' => 0, 'count' => 0, 'distribution' => []];
            $latestReviews = collect();
            
            try {
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

                // Get total count for "load more" functionality
                $totalComments = Comment::where('commentable_type', 'product')
                    ->where('commentable_id', $product->id)
                    ->whereNull('parent_id')
                    ->approved()
                    ->count();

                // Rating stats
                try {
                    $commentService = app(\App\Services\CommentService::class);
                    $ratingStats = $commentService->calculateRatingStats('product', $product->id);
                } catch (\Throwable $e) {
                    Log::warning('ProductController: Failed to calculate rating stats', ['error' => $e->getMessage()]);
                }

                // 5 đánh giá mới nhất cho schema Product
                try {
                    $latestReviews = Comment::where('commentable_type', 'product')
                        ->where('commentable_id', $product->id)
                        ->whereNull('parent_id')
                        ->approved()
                        ->whereNotNull('rating')
                        ->orderByDesc('created_at')
                        ->limit(5)
                        ->get();
                } catch (\Throwable $e) {
                    Log::warning('ProductController: Failed to load latest reviews', ['error' => $e->getMessage()]);
                }
            } catch (\Throwable $e) {
                Log::warning('ProductController: Failed to load comments', [
                    'product_id' => $product->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return view('clients.pages.single.index',
                compact('product', 'vouchers', 'productNew', 'productRelated', 'includedProducts', 'quantityProductDetail', 'comments', 'ratingStats', 'latestReviews', 'totalComments')
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
