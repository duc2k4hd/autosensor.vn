<?php

use App\Http\Controllers\Clients\AffiliateController as ClientAffiliateController;
use App\Http\Controllers\Clients\AiChatController;
use App\Http\Controllers\Clients\APIs\V1\GHNClientApiController as ClientGHNApiController;
use App\Http\Controllers\Clients\APIs\V1\VoucherController as ClientVoucherController;
use App\Http\Controllers\Clients\AuthController as ClientAuthController;
use App\Http\Controllers\Clients\BlogController as ClientBlogController;
use App\Http\Controllers\Clients\CartController as ClientCartController;
use App\Http\Controllers\Clients\CheckoutController;
use App\Http\Controllers\Clients\ContactController as ClientContactController;
use App\Http\Controllers\Clients\FlashSaleController as ClientFlashSaleController;
use App\Http\Controllers\Clients\HomeController as ClientHomeController;
use App\Http\Controllers\Clients\ImageController as ClientImageController;
use App\Http\Controllers\Clients\ImageSearchController as ClientImageSearchController;
use App\Http\Controllers\Clients\OrderController as ClientOrderController;
use App\Http\Controllers\Clients\PaymentController as ClientPaymentController;
use App\Http\Controllers\Clients\ProductController as ClientProductController;
use App\Http\Controllers\Clients\ProfileController as ClientProfileController;
use App\Http\Controllers\Clients\ShopController as ClientShopController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\NewsletterPublicController;
use App\Http\Controllers\SitemapPublicController;
use App\Models\Favorite;
use App\Models\Product;
use Illuminate\Support\Facades\Route;

// Trang chủ (với affiliate tracking)
Route::get('/', [ClientHomeController::class, 'index'])
    ->middleware(\App\Http\Middleware\TrackAffiliateClick::class)
    ->name('client.home.index');
Route::post('/newsletter/subscription', [\App\Http\Controllers\Clients\NewsletterController::class, 'subscription'])->name('client.newsletter.subscription');
Route::prefix('/kinh-nghiem')->name('client.blog.')->group(function () {
    Route::get('/', [ClientBlogController::class, 'index'])->name('index');
    Route::get('/{post:slug}', [ClientBlogController::class, 'show'])->name('show');
});
Route::get('/flash-sale', [ClientFlashSaleController::class, 'index'])->name('client.flash-sale.index');
Route::get('/cua-hang', [ClientShopController::class, 'index'])->name('client.shop.index');
Route::get('/san-pham/{slug}', [ClientProductController::class, 'detail'])->name('client.product.detail');
Route::post('/san-pham/phone-request', [ClientProductController::class, 'phoneRequest'])->name('client.product.phone-request');

// Giỏ hàng
Route::get('/gio-hang', [ClientCartController::class, 'index'])->name('client.cart.index');
Route::post('/gio-hang', [ClientCartController::class, 'store'])->name('client.cart.store');
Route::post('/gio-hang/cap-nhat', [ClientCartController::class, 'update'])->name('client.cart.update');
Route::delete('/gio-hang/xoa-het', [ClientCartController::class, 'removeAll'])->name('client.cart.remove.all');
Route::delete('/gio-hang/{cartItem}', [ClientCartController::class, 'removeItem'])->name('client.cart.remove.item');

// Yêu thích sản phẩm
Route::post('/san-pham/yeu-thich', [ClientProductController::class, 'wishlist'])->name('client.product.wishlist.add');
Route::delete('/san-pham/yeu-thich', [ClientProductController::class, 'wishlistRemove'])->name('client.product.wishlist.remove');

// Product Comparison
Route::prefix('so-sanh')->name('client.comparison.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Clients\ProductComparisonController::class, 'index'])->name('index');
    Route::post('/{productId}/add', [\App\Http\Controllers\Clients\ProductComparisonController::class, 'add'])->name('add');
    Route::delete('/{productId}/remove', [\App\Http\Controllers\Clients\ProductComparisonController::class, 'remove'])->name('remove');
    Route::delete('/clear', [\App\Http\Controllers\Clients\ProductComparisonController::class, 'clear'])->name('clear');
    Route::get('/count', [\App\Http\Controllers\Clients\ProductComparisonController::class, 'count'])->name('count');
});

Route::get('/thanh-toan', [CheckoutController::class, 'index'])->name('client.checkout.index');
Route::post('/thanh-toan', [CheckoutController::class, 'store'])->name('client.checkout.store');

// Payment Routes
Route::prefix('thanh-toan')->name('client.payment.')->group(function () {
    Route::get('/ket-qua', [ClientPaymentController::class, 'return'])->name('return');
    Route::get('/huy-bo', [ClientPaymentController::class, 'cancel'])->name('cancel');
});

// Route::get('/resize', [ClientImageController::class, 'resize'])->name('client.image.resize');

Route::get('/yeu-thich', function () {
    $accountId = auth('web')->id();
    $sessionId = session()->getId();

    $favorites = Favorite::with('product')
        ->ofOwner($accountId, $sessionId)
        ->latest()
        ->get();

    return view('clients.pages.favorites.index', compact('favorites'));
})->name('client.wishlist.index');

Route::get('/lien-he', [ClientContactController::class, 'show'])->name('client.contact.index');
Route::post('/lien-he', [ClientContactController::class, 'store'])->name('client.contact.store');

Route::get('/gioi-thieu', function () {
    $productNew = Product::active()->orderBy('created_at', 'desc')->inRandomOrder()->limit(9)->get() ?? [];

    return view('clients.pages.home.introduction', compact('productNew'));
})->name('client.introduction.index');

// Image Search (only API endpoint, no page)
Route::prefix('tim-kiem-hinh-anh')->name('client.image-search.')->group(function () {
    Route::post('/search', [ClientImageSearchController::class, 'search'])->name('search');
});

// Authentication
Route::prefix('/xac-thuc')->name('client.auth.')->group(function () {
    Route::get('/dang-nhap', [ClientAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/dang-nhap', [ClientAuthController::class, 'login'])->name('login.handle');
    Route::get('/dang-ky', [ClientAuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/dang-ky', [ClientAuthController::class, 'register'])->name('register.handle');
    Route::post('/dang-xuat', [ClientAuthController::class, 'logout'])->name('logout');
    Route::get('/xac-thuc-email/{token}', [ClientAuthController::class, 'verifyEmail'])->name('verify-email');
    Route::get('/quen-mat-khau', [ClientAuthController::class, 'showForgotPasswordForm'])->name('forgot-password');
    Route::post('/quen-mat-khau', [ClientAuthController::class, 'sendResetLink'])->name('forgot-password.send');
    Route::get('/dat-lai-mat-khau/{token}', [ClientAuthController::class, 'showResetPasswordForm'])->name('reset-password');
    Route::post('/dat-lai-mat-khau', [ClientAuthController::class, 'resetPassword'])->name('reset-password.handle');
});

// Profile (requires authentication)
Route::middleware(['auth:web'])->group(function () {
    Route::get('/tai-khoan', [ClientProfileController::class, 'index'])->name('client.profile.index');
    Route::put('/tai-khoan', [ClientProfileController::class, 'update'])->name('client.profile.update');
    Route::post('/tai-khoan/doi-mat-khau', [ClientProfileController::class, 'changePassword'])->name('client.profile.change-password');

    // Orders
    Route::prefix('don-hang')->name('client.order.')->group(function () {
        Route::get('/', [ClientOrderController::class, 'index'])->name('index');
        Route::get('/{code}', [ClientOrderController::class, 'show'])->name('show');
        Route::post('/{code}/huy', [ClientOrderController::class, 'cancel'])->name('cancel');
        Route::post('/{code}/mua-lai', [ClientOrderController::class, 'reorder'])->name('reorder');
        Route::get('/{code}/hoa-don', [ClientOrderController::class, 'invoice'])->name('invoice');
    });

    Route::get('/tai-khoan/don-hang/{code}', [ClientOrderController::class, 'show'])->name('client.orders.show');

    Route::get('/tra-cuu-van-don', [ClientOrderController::class, 'track'])->name('client.order.track');

    // Notifications
    Route::prefix('thong-bao')->name('client.notifications.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Clients\NotificationController::class, 'index'])->name('index');
        Route::post('{id}/read', [\App\Http\Controllers\Clients\NotificationController::class, 'markAsRead'])->name('read');
        Route::post('read-all', [\App\Http\Controllers\Clients\NotificationController::class, 'markAllAsRead'])->name('read-all');
        Route::delete('{id}', [\App\Http\Controllers\Clients\NotificationController::class, 'destroy'])->name('destroy');
        Route::delete('read/delete', [\App\Http\Controllers\Clients\NotificationController::class, 'deleteRead'])->name('delete-read');
        Route::get('unread-count', [\App\Http\Controllers\Clients\NotificationController::class, 'unreadCount'])->name('unread-count');
        Route::get('latest', [\App\Http\Controllers\Clients\NotificationController::class, 'latest'])->name('latest');
    });

    // Affiliate
    Route::prefix('affiliate')->name('client.affiliate.')->group(function () {
        Route::get('/', [ClientAffiliateController::class, 'index'])->name('index');
        Route::post('/copy-link', [ClientAffiliateController::class, 'copyLink'])->name('copy-link');
    });
});

Route::prefix('api/v1/ghn')->name('client.ghn.')->group(function () {
    Route::get('/province', [ClientGHNApiController::class, 'getProvince'])->name('province');
    Route::post('/district/{provinceId}', [ClientGHNApiController::class, 'getDistrict'])->name('district');
    Route::post('/ward/{districtId}', [ClientGHNApiController::class, 'getWard'])->name('ward');
    Route::get('/services/{districtId}', [ClientGHNApiController::class, 'getServices'])->name('services');
    Route::post('/calculate-fee', [ClientGHNApiController::class, 'calculateFee'])->name('fee');
});

Route::prefix('api/v1/cart')->name('client.cart.api.')->group(function () {
    Route::post('/accessories', [ClientCartController::class, 'addAccessory'])->name('accessories');
});

Route::prefix('api/v1/vouchers')->name('client.vouchers.')->group(function () {
    Route::post('/apply', [ClientVoucherController::class, 'apply'])->name('apply');
    Route::delete('/apply', [ClientVoucherController::class, 'remove'])->name('remove');
});

// Các trang chính sách
Route::get('/chinh-sach-doi-tra', fn () => view('clients.pages.policy.return'))->name('client.policy.return');
Route::get('/chinh-sach-ban-hang', fn () => view('clients.pages.policy.sale'))->name('client.policy.sale');
Route::get('/chinh-sach-bao-hanh', fn () => view('clients.pages.policy.warranty'))->name('client.policy.warranty');
Route::get('/dieu-khoan-su-dung', fn () => view('clients.pages.policy.terms'))->name('client.policy.terms');
Route::get('/chinh-sach-giao-hang', fn () => view('clients.pages.policy.delivery'))->name('client.policy.delivery');
Route::get('/chinh-sach-bao-mat', fn () => view('clients.pages.policy.privacy'))->name('client.policy.privacy');
Route::get('/chinh-sach-thanh-toan', fn () => view('clients.pages.policy.payment'))->name('client.policy.payment');

// Comments API
Route::prefix('api/comments')->name('comments.')->group(function () {
    Route::get('/', [CommentController::class, 'index'])->name('index');
    Route::get('/rating-stats', [CommentController::class, 'ratingStats'])->name('rating-stats');
    Route::get('/load-more', [CommentController::class, 'loadMore'])->name('load-more');
    Route::post('/', [CommentController::class, 'store'])->name('store');
});

Route::post('/api/ai/chat', AiChatController::class)
    ->middleware('throttle:20,1')
    ->name('client.ai.chat');

// Newsletter verify & unsubscribe
Route::get('/newsletter/verify/{token}', [NewsletterPublicController::class, 'verify'])->name('newsletter.verify');
Route::get('/newsletter/unsubscribe/{token}', [NewsletterPublicController::class, 'unsubscribe'])->name('newsletter.unsubscribe');

// Sitemap public endpoints
Route::get('/sitemap', [SitemapPublicController::class, 'landing'])->name('client.sitemap.landing');
Route::get('/sitemap.xml', [SitemapPublicController::class, 'index']);
Route::get('/sitemap-posts.xml', [SitemapPublicController::class, 'posts']);
// Các page sitemap posts bắt đầu từ page 2 để tránh trùng với sitemap-posts.xml
Route::get('/sitemap-posts-{page}.xml', [SitemapPublicController::class, 'posts'])
    ->whereNumber('page')
    ->where('page', '>=2');
Route::get('/sitemap-products.xml', [SitemapPublicController::class, 'products']);
// Các page sitemap products bắt đầu từ page 2 để tránh trùng với sitemap-products.xml
Route::get('/sitemap-products-{page}.xml', [SitemapPublicController::class, 'products'])
    ->whereNumber('page')
    ->where('page', '>=2');
Route::get('/sitemap-categories.xml', [SitemapPublicController::class, 'categories']);
Route::get('/sitemap-tags-products.xml', [SitemapPublicController::class, 'tagsProducts']);
Route::get('/sitemap-tags-posts.xml', [SitemapPublicController::class, 'tagsPosts']);
Route::get('/sitemap-pages.xml', [SitemapPublicController::class, 'pages']);
Route::get('/sitemap-images.xml', [SitemapPublicController::class, 'images']);

// Danh mục sản phẩm
Route::get('/{slug}', [ClientShopController::class, 'index'])->name('client.product.category.index');

// 404 fallback
Route::fallback(fn () => response()->view('clients.pages.errors.404', [], 404));
