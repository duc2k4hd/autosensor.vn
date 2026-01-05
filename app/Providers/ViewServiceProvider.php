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
            if (Schema::hasTable('settings')) {
                $settings = Cache::rememberForever('settings', function () {
                    return Setting::active()
                        ->get() // ❗ quan trọng
                        ->mapWithKeys(fn ($s) => [$s->key => $s->getParsedValue()])
                        ->toArray();
                });
                View::share('settings', (object) $settings);
            } else {
                View::share('settings', (object) []);
            }
        } catch (Throwable $e) {
            Log::warning('ViewServiceProvider: Failed to load settings', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            View::share('settings', (object) []);
        }

        // --- CATEGORIES ---
        try {
            if (Schema::hasTable('categories')) {
                $categories = Cache::remember('xanhworld_header_main_nav_category_lists', 3600, function () {
                    // Load tất cả categories active và build tree structure
                    $allCategories = Category::query()->active()
                        ->orderBy('order')
                        ->orderBy('name')
                        ->get();

                    // Build tree structure đệ quy
                    $buildTree = function ($category, $allCategories) use (&$buildTree) {
                        $children = $allCategories->where('parent_id', $category->id)->map(function ($child) use ($allCategories, &$buildTree) {
                            return $buildTree($child, $allCategories);
                        });

                        // Set children relationship
                        $category->setRelation('children', $children);

                        return $category;
                    };

                    return $allCategories->whereNull('parent_id')->map(function ($category) use ($allCategories, &$buildTree) {
                        return $buildTree($category, $allCategories);
                    });
                });

                View::share('categories', $categories);
            } else {
                View::share('categories', collect([]));
            }
        } catch (Throwable $e) {
            Log::warning('ViewServiceProvider: Failed to load categories', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            View::share('categories', collect([]));
        }

        // --- ACCOUNT + CART (Global composer) ---
        View::composer('*', function ($view) {
            // Không cache để đảm bảo luôn lấy dữ liệu mới nhất, đặc biệt sau khi đăng xuất
            try {
                $accountId = auth('web')->id();
                $sessionId = session()->getId() ?? null;
            } catch (Throwable $e) {
                // Nếu session chưa start (ví dụ Googlebot), dùng null
                Log::debug('ViewServiceProvider: Session not available', [
                    'error' => $e->getMessage(),
                ]);
                $accountId = null;
                $sessionId = null;
            }

            try {
                $account = auth('web')->user() ?? null;

                // Lấy cart: nếu đã đăng nhập thì lấy theo account_id, nếu chưa thì lấy theo session_id
                $cartQuery = Cart::query()->active()->with(['items' => function ($q) {
                    $q->where(function ($q2) {
                        $q2->whereNull('status')->orWhere('status', 'active');
                    });
                }]);

                if ($accountId) {
                    // Đã đăng nhập: chỉ lấy cart của account này
                    $cartQuery->where('account_id', $accountId);
                } else {
                    // Chưa đăng nhập: chỉ lấy cart theo session và không có account_id
                    // QUAN TRỌNG: Phải đảm bảo account_id là NULL để không lấy cart của user khác
                    if ($sessionId) {
                        $cartQuery->whereNull('account_id')->where('session_id', $sessionId);
                    } else {
                        // Nếu không có sessionId (ví dụ Googlebot), không lấy cart
                        $cartQuery->whereRaw('1 = 0');
                    }
                }

                $cart = $cartQuery->orderByDesc('id')->first();

                // Đếm số lượng cart items
                $cartItemSumQuery = CartItem::query()->active()
                    ->where(function ($q) {
                        $q->whereNull('status')->orWhere('status', 'active');
                    })
                    ->whereHas('cart', function ($q) use ($accountId, $sessionId) {
                        if ($accountId) {
                            $q->where('account_id', $accountId);
                        } else {
                            // QUAN TRỌNG: Phải đảm bảo account_id là NULL
                            if ($sessionId) {
                                $q->whereNull('account_id')->where('session_id', $sessionId);
                            } else {
                                // Nếu không có sessionId (ví dụ Googlebot), không lấy cart items
                                $q->whereRaw('1 = 0');
                            }
                        }
                    });

                $cartCount = (int) ($cartItemSumQuery->sum('quantity') ?? 0);
                $cartLink = $cartCount > 0 ? route('client.cart.index') : null;

                // Lấy favorites
                $favorites = Favorite::ofOwner($accountId, $sessionId)->pluck('product_id');
                $favCount = $favorites->count();
                $favIds = $favorites->toArray();
                $favLink = $favCount > 0 ? route('client.wishlist.index') : null;

                $sharedPayload = [
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
            } catch (Throwable $e) {
                Log::debug('Trình soạn thảo ViewServiceProvider đã bỏ qua', [
                    'error' => $e->getMessage(),
                ]);

                $sharedPayload = [
                    'account' => null,
                    'cart' => null,
                    'cartCount' => 0,
                    'cartLink' => null,
                    'cartQuantity' => 0,
                    'cartQty' => 0,
                    'cart_items_count' => 0,
                    'cartUrl' => null,
                    'wishlistCount' => 0,
                    'wishlistLink' => null,
                    'favoriteProductIds' => [],
                ];
            }

            foreach ($sharedPayload as $key => $value) {
                $view->with($key, $value);
            }

        });
    }

    /**
     * Build category tree structure đệ quy
     */
    private function buildCategoryTree(Category $category, $allCategories)
    {
        $children = $allCategories->where('parent_id', $category->id)->map(function ($child) use ($allCategories) {
            return $this->buildCategoryTree($child, $allCategories);
        });

        // Set children relationship
        $category->setRelation('children', $children);

        return $category;
    }
}
