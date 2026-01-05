<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use App\Models\Product;

class FlashSaleController extends Controller
{
    public function index()
    {
        $flashSale = FlashSale::query()
            ->with(['items' => function ($query) {
                $query->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->with(['product' => function ($productQuery) {
                        $productQuery->active()->with('primaryCategory');
                    }]);
            }])
            ->where('status', 'active')
            ->active()
            ->whereHas('items', function ($query) {
                $query->where('is_active', true)
                    ->whereHas('product', function ($productQuery) {
                        $productQuery->active();
                    });
            })
            ->orderBy('start_time')
            ->first();

        $flashSaleItems = collect();
        $stats = [
            'totalProducts' => 0,
            'totalStock' => 0,
            'totalSold' => 0,
            'maxDiscount' => 0,
        ];

        if ($flashSale) {
            $flashSaleItems = $flashSale->items
                ->filter(fn ($item) => $item->is_active && $item->product && $item->product->is_active)
                ->map(function ($item) {
                    $product = $item->product;
                    $originalPrice = $item->original_price ?? $product?->price ?? 0;
                    $salePrice = $item->sale_price ?? $item->unified_price ?? $product?->sale_price ?? $originalPrice;

                    $stock = $item->stock ?? $product?->stock_quantity ?? 0;
                    $sold = $item->sold ?? 0;
                    if ($stock > 0) {
                        $sold = min($sold, $stock);
                    }

                    $discountPercent = ($originalPrice > 0 && $salePrice && $salePrice < $originalPrice)
                        ? (int) round((($originalPrice - $salePrice) / $originalPrice) * 100)
                        : 0;

                    $progress = ($stock > 0)
                        ? (int) min(100, round(($sold / $stock) * 100))
                        : 0;

                    return [
                        'item' => $item,
                        'product' => $product,
                        'original_price' => (float) $originalPrice,
                        'sale_price' => (float) $salePrice,
                        'stock' => max((int) $stock, 0),
                        'sold' => max((int) $sold, 0),
                        'discount_percent' => max($discountPercent, 0),
                        'progress' => $progress,
                        'badges' => $this->buildBadges($discountPercent, $stock, $sold, $item->max_per_user),
                    ];
                })
                ->values();

            // Nếu không có items active thì không hiển thị flash sale
            if ($flashSaleItems->isEmpty()) {
                $flashSale = null;
                $flashSaleItems = collect();
            } else {
                Product::preloadImages($flashSaleItems->pluck('product')->filter());

                $stats = [
                    'totalProducts' => $flashSaleItems->count(),
                    'totalStock' => (int) $flashSaleItems->sum(fn ($entry) => $entry['stock']),
                    'totalSold' => (int) $flashSaleItems->sum(fn ($entry) => $entry['sold']),
                    'maxDiscount' => (int) $flashSaleItems->max(fn ($entry) => $entry['discount_percent']),
                ];

                $flashSale->increment('views');
            }
        }

        $upcomingFlashSales = FlashSale::query()
            ->where('is_active', true)
            ->where('status', 'active')
            ->where('start_time', '>', now())
            ->orderBy('start_time')
            ->take(4)
            ->get();

        $spotlightProducts = Product::active()
            ->whereDoesntHave('currentFlashSaleItem')
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get();

        Product::preloadImages($spotlightProducts);

        return view('clients.pages.flash-sale.index', [
            'flashSale' => $flashSale,
            'flashSaleItems' => $flashSaleItems,
            'stats' => $stats,
            'upcomingFlashSales' => $upcomingFlashSales,
            'spotlightProducts' => $spotlightProducts,
        ]);
    }

    protected function buildBadges(int $discountPercent, ?int $stock, ?int $sold, ?int $maxPerUser): array
    {
        $badges = [];

        if ($discountPercent >= 50) {
            $badges[] = 'Deal sốc';
        } elseif ($discountPercent >= 30) {
            $badges[] = 'Giảm sâu';
        }

        if ($stock && $sold && $stock > 0) {
            $soldPercent = ($sold / $stock) * 100;
            if ($soldPercent >= 70) {
                $badges[] = 'Sắp cháy hàng';
            }
        }

        if ($maxPerUser) {
            $badges[] = 'Giới hạn '.$maxPerUser.'/người';
        }

        return $badges;
    }
}
