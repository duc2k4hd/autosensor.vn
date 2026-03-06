<?php

namespace App\Providers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Favorite;
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
        } catch (Throwable $e) {
            Log::warning('ViewServiceProvider: Failed to load categories', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            View::share('categories', collect([]));
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

}
