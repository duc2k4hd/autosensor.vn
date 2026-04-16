<?php

namespace App\Providers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Favorite;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Collection;
use Throwable;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void {}

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // --- SETTINGS ---
        try {
            $settings = Cache::rememberForever('settings', function () {
                return Setting::active()
                    ->get()
                    ->mapWithKeys(fn ($s) => [$s->key => $s->getParsedValue()])
                    ->toArray();
            });
            View::share('settings', (object) $settings);
        } catch (Throwable $e) {
            Log::warning('ViewServiceProvider: Failed to load settings', ['error' => $e->getMessage()]);
            View::share('settings', (object) []);
        }

        // --- CATEGORIES (Tối ưu Eager Tree < 10ms - Bản vá an toàn v6) ---
        try {
            $categories = Cache::remember('autosensor_header_main_nav_category_lists_v6', 86400, function () {
                // Sử dụng Eloquent Model để giữ tính tương thích với View (pluck, relations, ...)
                // nhưng nạp phẳng một lần duy nhất để tránh đệ quy chậm
                $items = Category::active()
                    ->select(['id', 'parent_id', 'name', 'slug', 'image', 'order', 'metadata'])
                    ->orderBy('order', 'asc')
                    ->get();

                $lookup = [];
                foreach ($items as $item) {
                    $item->setRelation('children', collect([]));
                    $lookup[$item->id] = $item;
                }

                $tree = collect([]);
                foreach ($lookup as $item) {
                    if (empty($item->parent_id)) {
                        $tree->push($item);
                    } elseif (isset($lookup[$item->parent_id])) {
                        $lookup[$item->parent_id]->children->push($item);
                    }
                }
                return $tree;
            });
            View::share('categories', $categories);
            View::share('headerCategoryProducts', $this->resolveHeaderCategoryProducts($categories));
        } catch (Throwable $e) {
            Log::warning('ViewServiceProvider: Failed to load categories', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            View::share('categories', collect([]));
            View::share('headerCategoryProducts', []);
        }

        // --- ACCOUNT + CART (Global composer) ---
        // Dùng static để chỉ query 1 lần/request dù composer('*') được gọi nhiều lần
        View::composer('*', function ($view) {
            static $resolved = null;

            if ($resolved === null) {
                $resolved = $this->resolveSharedPayload();
            }

            foreach ($resolved as $key => $value) {
                $view->with($key, $value);
            }
        });
    }

    /**
     * Tính toán cart/account/favorites — chỉ gọi 1 lần/request nhờ static cache trong composer.
     */
    private function resolveSharedPayload(): array
    {
        try {
            $accountId = auth('web')->id();
            $sessionId = session()->getId() ?? 'guest';
            $cacheKey = "shared_payload_u{$accountId}_s{$sessionId}";

            // Cache ngắn hạn 30 giây để tránh query lặp lại khi chuyển trang liên tục
            return Cache::remember($cacheKey, 30, function () use ($accountId, $sessionId) {
                $account = auth('web')->user() ?? null;
                $cachedCartId = session()->get('active_cart_id');
                $cart = null;

                if ($cachedCartId) {
                    $cart = Cart::query()->active()->with(['items' => function ($q) {
                        $q->where(function ($q2) {
                            $q2->whereNull('status')->orWhere('status', 'active');
                        });
                    }])->find($cachedCartId);

                    if ($cart) {
                        $isValid = ($accountId && (int)$cart->account_id === (int)$accountId)
                                || (!$accountId && $cart->session_id === $sessionId);
                        if (!$isValid) {
                            $cart = null;
                            session()->forget('active_cart_id');
                        }
                    }
                }

                if (!$cart) {
                    $cartQuery = Cart::query()->active()->with(['items' => function ($q) {
                        $q->where(function ($q2) {
                            $q2->whereNull('status')->orWhere('status', 'active');
                        });
                    }]);

                    if ($accountId) {
                        $cartQuery->where('account_id', $accountId);
                    } else {
                        $cartQuery->whereNull('account_id')->where('session_id', $sessionId);
                    }

                    $cart = $cartQuery->orderByDesc('id')->first();
                    if ($cart) {
                        session()->put('active_cart_id', $cart->id);
                    }
                }

                $cartCount = $cart ? (int) $cart->items->sum('quantity') : 0;
                $cartLink = $cartCount > 0 ? route('client.cart.index') : null;

                $favorites = Favorite::ofOwner($accountId, $sessionId)->pluck('product_id');
                $favCount = $favorites->count();
                $favIds = $favorites->toArray();
                $favLink = $favCount > 0 ? route('client.wishlist.index') : null;

                return [
                    'account' => $account,
                    'cart' => $cart,
                    'cartCount' => $cartCount,
                    'cartLink' => $cartLink,
                    'cartQuantity' => $cartCount,
                    'cartQty' => $cartCount,
                    'cart_items_count' => $cartCount,
                    'cartUrl' => $cartLink,
                    'wishlistCount' => $favCount,
                    'wishlistLink' => $favLink,
                    'favoriteProductIds' => $favIds,
                ];
            });
        } catch (Throwable $e) {
            Log::debug('ViewServiceProvider: resolveSharedPayload failed', ['error' => $e->getMessage()]);
            return [
                'account' => null, 'cart' => null, 'cartCount' => 0, 'cartLink' => null,
                'cartQuantity' => 0, 'cartQty' => 0, 'cart_items_count' => 0, 'cartUrl' => null,
                'wishlistCount' => 0, 'wishlistLink' => null, 'favoriteProductIds' => [],
            ];
        }
    }

    private function resolveHeaderCategoryProducts($categories): array
    {
        try {
            $rootCategoryIds = [];

            foreach ($categories as $category) {
                $childIds = $category->children?->pluck('id')->all() ?? [];
                $rootCategoryIds[$category->id] = array_values(array_unique(array_merge([$category->id], $childIds)));
            }

            if (empty($rootCategoryIds)) {
                return [];
            }

            $signature = md5(json_encode($rootCategoryIds));
            $cacheKey = "header_category_products_map_v2_{$signature}";

            return Cache::remember($cacheKey, 86400, function () use ($rootCategoryIds) {
                $selectColumns = [
                    'id',
                    'name',
                    'slug',
                    'price',
                    'sale_price',
                    'primary_category_id',
                    'category_ids',
                    'is_featured',
                    'created_at',
                    'image_ids',
                ];

                $allCategoryIds = collect($rootCategoryIds)->flatten()->unique()->values()->all();
                $featuredCandidates = $this->queryHeaderProducts($selectColumns, $allCategoryIds, true, max(120, count($rootCategoryIds) * 20));
                $fallbackCandidates = $this->queryHeaderProducts($selectColumns, $allCategoryIds, false, max(200, count($rootCategoryIds) * 30));

                $mapped = [];

                foreach ($rootCategoryIds as $rootId => $categoryIds) {
                    $products = $this->pickHeaderProductsForCategory($featuredCandidates, $categoryIds);

                    if ($products->isEmpty()) {
                        $products = Product::active()
                            ->featured()
                            ->select($selectColumns)
                            ->inCategory($categoryIds)
                            ->latest('id')
                            ->limit(5)
                            ->get();
                        Product::preloadImages($products);
                    }

                    if ($products->isEmpty()) {
                        $products = $this->pickHeaderProductsForCategory($fallbackCandidates, $categoryIds);
                    }

                    if ($products->isEmpty()) {
                        $products = Product::active()
                            ->select($selectColumns)
                            ->inCategory($categoryIds)
                            ->latest('id')
                            ->limit(5)
                            ->get();
                        Product::preloadImages($products);
                    }

                    $mapped[$rootId] = $products
                        ->take(5)
                        ->values()
                        ->map(fn (Product $product) => $this->mapHeaderProduct($product))
                        ->all();
                }

                return $mapped;
            });
        } catch (Throwable $e) {
            Log::warning('ViewServiceProvider: Failed to resolve header category products', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    private function queryHeaderProducts(array $selectColumns, array $categoryIds, bool $featuredOnly, int $limit)
    {
        $query = Product::active()
            ->select($selectColumns);

        if ($featuredOnly) {
            $query->featured();
        }

        $query->where(function ($builder) use ($categoryIds) {
            $builder->whereIn('primary_category_id', $categoryIds);

            foreach ($categoryIds as $categoryId) {
                $builder->orWhereJsonContains('category_ids', (int) $categoryId);
            }
        })
            ->latest('id')
            ->limit($limit);

        $products = $query->get();
        Product::preloadImages($products);

        return $products;
    }

    private function pickHeaderProductsForCategory($products, array $categoryIds)
    {
        return $products
            ->filter(fn (Product $product) => $this->headerProductBelongsToCategories($product, $categoryIds))
            ->take(5)
            ->values();
    }

    private function headerProductBelongsToCategories(Product $product, array $categoryIds): bool
    {
        if (in_array((int) $product->primary_category_id, $categoryIds, true)) {
            return true;
        }

        $extraCategoryIds = $product->category_ids ?? [];

        if (! is_array($extraCategoryIds) || empty($extraCategoryIds)) {
            return false;
        }

        $categoryLookup = array_flip(array_map('intval', $categoryIds));

        foreach ($extraCategoryIds as $categoryId) {
            if (isset($categoryLookup[(int) $categoryId])) {
                return true;
            }
        }

        return false;
    }

    private function mapHeaderProduct(Product $product): array
    {
        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'name' => $product->name,
            'label' => $product->label,
            'frame' => $product->frame,
            'price' => (float) ($product->price ?? 0),
            'sale_price' => $product->sale_price !== null ? (float) $product->sale_price : null,
            'image_url' => $product->primaryImage?->url ?? 'no-image.webp',
        ];
    }

}
