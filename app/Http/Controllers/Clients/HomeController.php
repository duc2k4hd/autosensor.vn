<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\FlashSale;
use App\Models\Product;
use App\Models\Voucher;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index()
    {
        $banners_home_parent = Cache::remember('banners_home_parent', now()->addDays(30), function () {
            return Banner::active()->where('position', 'homepage_banner_parent')->get();
        });
        $banners_home_children = Cache::remember('banners_home_children', now()->addDays(30), function () {
            return Banner::active()->where('position', 'homepage_banner_children')->get();
        });
        $vouchers = Cache::remember('vouchers_home', now()->addDays(30), function () {
            return Voucher::active()->limit(3)->get();
        });
        $productsFeatured = Cache::remember('products_featured_home', now()->addDays(30), function () {
            $products = Product::active()
                ->featured()
                ->withApprovedCommentsMeta()
                ->with('variants')
                ->take(18)
                ->get() ?? collect();
            Product::preloadImages($products);

            return $products;
        });
        // Không cần gọi lại preloadImages vì đã được preload trong cache callback

        $productRandom = Cache::remember('products_random_home', now()->addDays(30), function () {
            $products = Product::active()
                ->withApprovedCommentsMeta()
                ->with('variants')
                ->when(
                    $category = Category::where('slug', 'cay-phong-thuy')->first(),
                    function ($query) use ($category) {
                        $query->inCategory([$category->id]);
                    }
                )->inRandomOrder()->limit(20)->get();

            Product::preloadImages($products);

            return $products;
        });
        // Không cần gọi lại preloadImages vì đã được preload trong cache callback

        // Tối ưu: Cache ngắn hơn (60 giây) để đảm bảo dữ liệu realtime, nhưng vẫn cache để giảm query
        // Tối ưu query: Tìm flash sale ID trước, sau đó load đầy đủ với relationships
        $flashSale = Cache::remember('flash_sale_data', 60, function () {
            $now = now();
            
            // Bước 1: Tìm flash sale ID có items hợp lệ (dùng join để tối ưu)
            $flashSaleId = FlashSale::where('flash_sales.is_active', true)
                ->where('flash_sales.status', 'active')
                ->where('flash_sales.start_time', '<=', $now)
                ->where('flash_sales.end_time', '>=', $now)
                // Join với items để filter items active và còn hàng
                ->join('flash_sale_items', function ($join) {
                    $join->on('flash_sales.id', '=', 'flash_sale_items.flash_sale_id')
                        ->where('flash_sale_items.is_active', true)
                        ->whereRaw('flash_sale_items.stock > flash_sale_items.sold');
                })
                // Join với products để filter products active và còn hàng
                ->join('products', function ($join) {
                    $join->on('flash_sale_items.product_id', '=', 'products.id')
                        ->where('products.is_active', true)
                        ->where('products.stock_quantity', '>', 0);
                })
                ->select('flash_sales.id')
                ->groupBy('flash_sales.id')
                ->orderBy('flash_sales.start_time', 'desc')
                ->value('flash_sales.id');
            
            // Bước 2: Nếu có ID, load đầy đủ flash sale với relationships
            if (!$flashSaleId) {
                return null;
            }
            
            return FlashSale::where('id', $flashSaleId)
                ->first()
                ?->makeHidden([
                    'start_time', 'end_time', 'created_at', 'updated_at',
                ]);
        });

        // Nếu có flash sale, load items và products với eager loading
        if ($flashSale) {
            // Load items với điều kiện tương tự như query trên
            $flashSale->load([
                'items' => function ($query) {
                    $query->where('is_active', true)
                        ->whereRaw('stock > sold')
                        ->whereHas('product', function ($productQuery) {
                            $productQuery->where('is_active', true)
                                ->where('stock_quantity', '>', 0);
                        })
                        ->orderBy('sort_order')
                        ->orderBy('id');
                },
                'items.product' => function ($productQuery) {
                    $productQuery->where('is_active', true)->withApprovedCommentsMeta();
                },
                'items.product.primaryCategory',
            ]);

            // Kiểm tra lại điều kiện realtime từ data đã load (không query lại DB)
            $now = now();
            if (!$flashSale->is_active 
                || $flashSale->status !== 'active'
                || ($flashSale->start_time && $flashSale->start_time->gt($now))
                || ($flashSale->end_time && $flashSale->end_time->lt($now))) {
                // Flash sale không còn đang chạy
                $flashSale = null;
            } else {
                // Lọc lại items để đảm bảo chỉ lấy items active và còn hàng
                $filteredItems = $flashSale->items->filter(function ($item) {
                    return $item->is_active
                        && ($item->stock > $item->sold)
                        && $item->product
                        && $item->product->is_active
                        && ($item->product->stock_quantity > 0);
                });

                // Nếu không có items active thì không hiển thị flash sale
                if ($filteredItems->isEmpty()) {
                    $flashSale = null;
                } else {
                    $flashSale->setRelation('items', $filteredItems);
                    // Preload images cho products
                    Product::preloadImages(
                        $filteredItems->pluck('product')->filter()
                    );
                }
            }
        }

        // Lấy danh sách brands (đối tác) để hiển thị
        $partners = Cache::remember('partners_home', now()->addDays(30), function () {
            return Brand::where('is_active', true)
                ->whereNotNull('image')
                ->where('image', '!=', '')
                ->orderBy('order')
                ->orderBy('name')
                ->get();
        });

        // Lấy 5 danh mục đầu tiên và sản phẩm của mỗi danh mục cho tabs
        $categoriesForTabs = Cache::remember('categories_tabs_home', now()->addDays(7), function () {
            return Category::active()
                ->whereNull('parent_id')
                ->orderBy('order')
                ->orderBy('name')
                ->take(5)
                ->get();
        });

        $categoryProducts = [];
        foreach ($categoriesForTabs as $category) {
            $categoryProducts[$category->id] = Cache::remember("category_products_tab_{$category->id}", now()->addDays(7), function () use ($category) {
                $products = Product::active()
                    ->inCategory([$category->id])
                    ->withApprovedCommentsMeta()
                    ->with('variants')
                    ->orderBy('created_at', 'desc')
                    ->take(10)
                    ->get();
                Product::preloadImages($products);
                return $products;
            });
        }

        // Tính product counts cho tất cả category children để tránh N+1 query trong view
        $categoryProductCounts = Cache::remember('category_product_counts_home', now()->addHours(6), function () {
            // Lấy tất cả category children IDs từ $categories (đã được share trong ViewServiceProvider)
            $categories = \Illuminate\Support\Facades\View::shared('categories') ?? collect();
            $childCategoryIds = [];
            
            foreach ($categories as $category) {
                if ($category->children && $category->children->isNotEmpty()) {
                    foreach ($category->children as $child) {
                        $childCategoryIds[] = $child->id;
                    }
                }
            }
            
            if (empty($childCategoryIds)) {
                return [];
            }
            
            // Tính counts cho tất cả categories một lần bằng cách query database hiệu quả
            $counts = [];
            
            // Query counts cho primary_category_id
            $primaryCounts = Product::active()
                ->whereIn('primary_category_id', $childCategoryIds)
                ->groupBy('primary_category_id')
                ->selectRaw('primary_category_id, COUNT(*) as count')
                ->pluck('count', 'primary_category_id')
                ->toArray();
            
            // Query counts cho category_ids (JSON array)
            // Lấy tất cả products có category_ids chứa bất kỳ child category nào
            $productsWithCategoryIds = Product::active()
                ->whereNotNull('category_ids')
                ->where('category_ids', '!=', '[]')
                ->select('id', 'category_ids')
                ->get();
            
            // Tính counts cho category_ids
            $categoryIdsCounts = [];
            foreach ($productsWithCategoryIds as $product) {
                $productCategoryIds = $product->category_ids ?? [];
                if (is_array($productCategoryIds)) {
                    foreach ($productCategoryIds as $catId) {
                        $catId = (int)$catId; // Normalize to int
                        if (in_array($catId, $childCategoryIds)) {
                            $categoryIdsCounts[$catId] = ($categoryIdsCounts[$catId] ?? 0) + 1;
                        }
                    }
                }
            }
            
            // Merge counts (một product có thể thuộc nhiều categories)
            foreach ($childCategoryIds as $categoryId) {
                $count = ($primaryCounts[$categoryId] ?? 0) + ($categoryIdsCounts[$categoryId] ?? 0);
                $counts[$categoryId] = $count;
            }
            
            return $counts;
        });

        return view('clients.pages.home.index', compact('banners_home_parent', 'banners_home_children', 'vouchers', 'productsFeatured', 'productRandom', 'flashSale', 'partners', 'categoriesForTabs', 'categoryProducts', 'categoryProductCounts'));
    }
}
