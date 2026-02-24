<?php

namespace App\Providers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Favorite;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
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

        // --- CATEGORIES ---
        try {
            $categories = Cache::remember('autosensor_header_main_nav_category_lists', 3600, function () {
                $allCategories = Category::query()->active()->orderBy('order')->get();

                $buildTree = function ($category, $allCategories) use (&$buildTree) {
                    $children = $allCategories->where('parent_id', $category->id)->map(
                        fn ($child) => $buildTree($child, $allCategories)
                    );
                    $category->setRelation('children', $children);
                    return $category;
                };

                return $allCategories->whereNull('parent_id')->map(
                    fn ($category) => $buildTree($category, $allCategories)
                );
            });
            View::share('categories', $categories);
        } catch (Throwable $e) {
            Log::warning('ViewServiceProvider: Failed to load categories', ['error' => $e->getMessage()]);
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
            $accountId  = auth('web')->id();
            $sessionId  = session()->getId() ?? null;
        } catch (Throwable $e) {
            Log::debug('ViewServiceProvider: Session not available', ['error' => $e->getMessage()]);
            $accountId = null;
            $sessionId = null;
        }

        try {
            $account = auth('web')->user() ?? null;

            // [LEVEL 5] Cart Session Caching: Thử lấy ID giỏ hàng từ session để tìm theo PK (nhanh nhất)
            $cachedCartId = session()->get('active_cart_id');
            $cart = null;

            if ($cachedCartId) {
                $cart = Cart::query()->active()->with(['items' => function ($q) {
                    $q->where(function ($q2) {
                        $q2->whereNull('status')->orWhere('status', 'active');
                    });
                }])->find($cachedCartId);

                // Kiểm tra tính hợp lệ: giỏ hàng phải thuộc về user/session hiện tại
                if ($cart) {
                    $isValid = false;
                    if ($accountId && (int)$cart->account_id === (int)$accountId) {
                        $isValid = true;
                    } elseif (!$accountId && $sessionId && $cart->session_id === $sessionId) {
                        $isValid = true;
                    }

                    if (!$isValid) {
                        $cart = null;
                        session()->forget('active_cart_id');
                    }
                }
            }

            // Fallback: Nếu chưa có trong session hoặc ID không hợp lệ, query theo cách cũ
            if (!$cart) {
                $cartQuery = Cart::query()->active()->with(['items' => function ($q) {
                    $q->where(function ($q2) {
                        $q2->whereNull('status')->orWhere('status', 'active');
                    });
                }]);

                if ($accountId) {
                    $cartQuery->where('account_id', $accountId);
                } elseif ($sessionId) {
                    $cartQuery->whereNull('account_id')->where('session_id', $sessionId);
                } else {
                    $cartQuery->whereRaw('1 = 0');
                }

                $cart = $cartQuery->orderByDesc('id')->first();

                // Lưu lại vào session cho request sau
                if ($cart) {
                    session()->put('active_cart_id', $cart->id);
                }
            }

            // Đếm từ items đã load — không cần query CartItem riêng
            $cartCount = $cart ? (int) $cart->items->sum('quantity') : 0;
            $cartLink  = $cartCount > 0 ? route('client.cart.index') : null;

            // 1 query: lấy favorites
            $favorites = Favorite::ofOwner($accountId, $sessionId)->pluck('product_id');
            $favCount  = $favorites->count();
            $favIds    = $favorites->toArray();
            $favLink   = $favCount > 0 ? route('client.wishlist.index') : null;

            return [
                'account'            => $account,
                'cart'               => $cart,
                'cartCount'          => $cartCount,
                'cartLink'           => $cartLink,
                'cartQuantity'       => $cartCount,
                'cartQty'            => $cartCount,
                'cart_items_count'   => $cartCount,
                'cartUrl'            => $cartLink,
                'wishlistCount'      => $favCount,
                'wishlistLink'       => $favLink,
                'favoriteProductIds' => $favIds,
            ];
        } catch (Throwable $e) {
            Log::debug('ViewServiceProvider: resolveSharedPayload failed', ['error' => $e->getMessage()]);

            return [
                'account'            => null,
                'cart'               => null,
                'cartCount'          => 0,
                'cartLink'           => null,
                'cartQuantity'       => 0,
                'cartQty'            => 0,
                'cart_items_count'   => 0,
                'cartUrl'            => null,
                'wishlistCount'      => 0,
                'wishlistLink'       => null,
                'favoriteProductIds' => [],
            ];
        }
    }

}
