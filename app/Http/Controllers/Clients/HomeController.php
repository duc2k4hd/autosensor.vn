<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Banner;
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
        Product::preloadImages($productsFeatured);

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
        Product::preloadImages($productRandom);

        $flashSale = Cache::remember('flash_sale_data', 3600, function () {
            return FlashSale::where('is_active', true)
                ->where('status', 'active')
                ->where('start_time', '<=', now())  // Đã bắt đầu
                ->where('end_time', '>=', now())    // Chưa kết thúc
                ->whereHas('items', function ($query) {
                    $query->where('is_active', true)
                        ->whereRaw('stock > sold')
                        ->whereHas('product', function ($productQuery) {
                            $productQuery->where('is_active', true)
                                ->where('stock_quantity', '>', 0);
                        });
                })
                ->orderBy('start_time', 'desc')
                ->with([
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
                ])
                ->first()
                ?->makeHidden([
                    'start_time', 'end_time', 'created_at', 'updated_at',
                ]);
        });

        if ($flashSale) {
            Product::preloadImages(
                $flashSale->items->pluck('product')->filter()
            );
        }

        // 2. Lấy thời gian realtime và kiểm tra lại điều kiện
        if ($flashSale) {
            $flashSaleTime = FlashSale::where('id', $flashSale->id)
                ->select('id', 'start_time', 'end_time', 'is_active', 'status')
                ->first();

            // 3. Kiểm tra lại điều kiện: phải đang chạy (không phải scheduled)
            if ($flashSaleTime
                && $flashSaleTime->is_active
                && $flashSaleTime->status === 'active'
                && $flashSaleTime->start_time <= now()
                && $flashSaleTime->end_time >= now()) {

                $flashSale->start_time = $flashSaleTime->start_time;
                $flashSale->end_time = $flashSaleTime->end_time;

                // Lọc lại items nếu cần (đảm bảo chỉ lấy items active và còn hàng)
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
                }
            } else {
                // Flash sale không còn đang chạy (đã tắt, đã kết thúc, hoặc chưa bắt đầu)
                $flashSale = null;
            }
        }

        return view('clients.pages.home.index', compact('banners_home_parent', 'banners_home_children', 'vouchers', 'productsFeatured', 'productRandom', 'flashSale'));
    }
}
