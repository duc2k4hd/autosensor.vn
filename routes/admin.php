<?php

use App\Http\Controllers\Admins\AccountController;
use App\Http\Controllers\Admins\AdminNewsletterController;
use App\Http\Controllers\Admins\AuthController;
use App\Http\Controllers\Admins\CartController as AdminCartController;
use App\Http\Controllers\Admins\ContactController as AdminContactController;
use App\Http\Controllers\Admins\ImportExcelController;
use App\Http\Controllers\Admins\MediaController;
use App\Http\Controllers\Admins\OrderController as AdminOrderController;
use App\Http\Controllers\Admins\ProductController;
use App\Http\Controllers\Admins\SitemapController;
use Illuminate\Support\Facades\Route;

// Admin Login (public)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

// Admin Routes (protected)
// Chỉ dùng 'admin' middleware vì CheckAdmin đã xử lý authentication và redirect
Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/', [\App\Http\Controllers\Admins\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [\App\Http\Controllers\Admins\DashboardController::class, 'index'])->name('dashboard');

    // Account Management
    Route::resource('accounts', AccountController::class);

    // Category Management
    Route::resource('categories', \App\Http\Controllers\Admins\CategoryController::class);
    Route::post('categories/{category}/toggle-status', [\App\Http\Controllers\Admins\CategoryController::class, 'toggleStatus'])->name('categories.toggle-status');
    Route::patch('categories/{category}/update-parent', [\App\Http\Controllers\Admins\CategoryController::class, 'updateParent'])->name('categories.update-parent');
    Route::post('categories/bulk-action', [\App\Http\Controllers\Admins\CategoryController::class, 'bulkAction'])->name('categories.bulk-action');
    Route::post('categories/reorder', [\App\Http\Controllers\Admins\CategoryController::class, 'reorder'])->name('categories.reorder');
    Route::get('categories/tree', [\App\Http\Controllers\Admins\CategoryController::class, 'tree'])->name('categories.tree');
    Route::get('api/categories/{category}', [\App\Http\Controllers\Admins\CategoryController::class, 'apiShow'])->name('api.categories.show');

    // Product Management
    Route::resource('products', ProductController::class)->except(['show']);
    Route::get('products/media-images', [ProductController::class, 'getMediaImagesApi'])->name('products.media-images');

    // Product Actions
    Route::post('products/bulk-action', [ProductController::class, 'bulkAction'])->name('products.bulk-action');
    Route::post('products/{product}/release-lock', [ProductController::class, 'releaseLock'])->name('products.release-lock');
    Route::post('products/{product}/restore', [ProductController::class, 'restore'])->name('products.restore');
    Route::get('products/{product}/inventory', [ProductController::class, 'inventory'])->name('products.inventory');
    Route::post('products/{product}/inventory-adjust', [ProductController::class, 'inventoryAdjust'])->name('products.inventory-adjust');
    Route::post('products/upload-cropped-image', [ProductController::class, 'uploadCroppedImage'])->name('products.upload-cropped-image');

    // Product Import/Export
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/import-excel', [ImportExcelController::class, 'index'])->name('import-excel');
        Route::post('/import-excel', [ImportExcelController::class, 'import'])->name('import-excel.import');
        Route::post('/import-excel/process', [ImportExcelController::class, 'import'])->name('import-excel.process');
        Route::get('/export-excel', [ImportExcelController::class, 'export'])->name('export-excel');
    });

    // Account Actions
    Route::post('accounts/{account}/lock', [AccountController::class, 'lock'])->name('accounts.lock');
    Route::post('accounts/{account}/unlock', [AccountController::class, 'unlock'])->name('accounts.unlock');
    Route::patch('accounts/{account}/toggle', [AccountController::class, 'toggle'])->name('accounts.toggle');
    Route::post('accounts/{account}/ban', [AccountController::class, 'ban'])->name('accounts.ban');
    Route::post('accounts/{account}/unban', [AccountController::class, 'unban'])->name('accounts.unban');
    Route::post('accounts/{account}/reset-password', [AccountController::class, 'resetPassword'])->name('accounts.reset-password');
    Route::post('accounts/{account}/verify-email', [AccountController::class, 'verifyEmail'])->name('accounts.verify-email');
    Route::post('accounts/{account}/reset-login-attempts', [AccountController::class, 'resetLoginAttempts'])->name('accounts.reset-login-attempts');
    Route::post('accounts/{id}/restore', [AccountController::class, 'restore'])->name('accounts.restore');

    // Bulk Actions
    Route::post('accounts/bulk-action', [AccountController::class, 'bulkAction'])->name('accounts.bulk-action');

    // Export
    Route::get('accounts/export/excel', [AccountController::class, 'export'])->name('accounts.export');

    // Dashboard
    Route::get('accounts/dashboard', [AccountController::class, 'dashboard'])->name('accounts.dashboard');

    // API Routes for Account Edit Page
    Route::prefix('api/accounts')->name('api.accounts.')->group(function () {
        Route::get('{account}', [AccountController::class, 'apiShow'])->name('show');
        Route::put('{account}', [AccountController::class, 'apiUpdate'])->name('update');
        Route::patch('{account}/toggle', [AccountController::class, 'apiToggle'])->name('toggle');
        Route::post('{account}/change-role', [AccountController::class, 'apiChangeRole'])->name('change-role');
        Route::patch('{account}/reset-password', [AccountController::class, 'apiResetPassword'])->name('reset-password');
        Route::post('{account}/force-logout', [AccountController::class, 'apiForceLogout'])->name('force-logout');
        Route::post('{account}/verify-email', [AccountController::class, 'apiVerifyEmail'])->name('verify-email');

        // Profile API
        Route::prefix('{account}/profile')->name('profile.')->group(function () {
            Route::get('/', [AccountController::class, 'apiProfileShow'])->name('show');
            Route::put('/', [AccountController::class, 'apiProfileUpdate'])->name('update');
            Route::patch('/visibility', [AccountController::class, 'apiProfileVisibility'])->name('visibility');
            Route::post('/avatar', [AccountController::class, 'apiProfileAvatar'])->name('avatar');
        });

        // Logs API
        Route::prefix('{account}/logs')->name('logs.')->group(function () {
            Route::get('/', [AccountController::class, 'apiLogsIndex'])->name('index');
            Route::get('/export', [AccountController::class, 'apiLogsExport'])->name('export');
        });
    });

    // Orders
    // Các route tĩnh phải khai báo trước resource để không bị trùng với `orders/{order}`
    Route::get('orders/get-pick-shifts', [AdminOrderController::class, 'getPickShifts'])->name('orders.get-pick-shifts');
    Route::get('orders/track', [AdminOrderController::class, 'track'])->name('orders.track');
    Route::post('orders/track', [AdminOrderController::class, 'trackPost'])->name('orders.track.post');

    Route::resource('orders', AdminOrderController::class);
    Route::patch('orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::patch('orders/{order}/cancel', [AdminOrderController::class, 'cancel'])->name('orders.cancel');
    Route::patch('orders/{order}/complete', [AdminOrderController::class, 'complete'])->name('orders.complete');
    Route::post('orders/{order}/recalculate', [AdminOrderController::class, 'recalculate'])->name('orders.recalculate');
    Route::get('orders/{order}/invoice', [AdminOrderController::class, 'invoice'])->name('orders.invoice');
    Route::get('orders/{order}/invoice-pdf', [AdminOrderController::class, 'invoicePdf'])->name('orders.invoice.pdf');
    Route::post('orders/{order}/create-ghn', [AdminOrderController::class, 'createGHNOrder'])->name('orders.create-ghn');
    Route::post('orders/{order}/sync-ghn', [AdminOrderController::class, 'syncGHNOrder'])->name('orders.sync-ghn');
    Route::get('orders/{order}/edit-ghn', [AdminOrderController::class, 'editGHNOrder'])->name('orders.edit-ghn');
    Route::get('orders/{order}/print-ghn', [AdminOrderController::class, 'printGHNOrder'])->name('orders.print-ghn');
    Route::post('orders/{order}/shipping-status', [AdminOrderController::class, 'storeShippingStatus'])->name('orders.shipping-status.store');
    Route::get('orders/{order}/shipping-status', [AdminOrderController::class, 'getShippingStatusHistory'])->name('orders.shipping-status.history');
    Route::post('orders/{order}/sync-ghn-ticket-list', [AdminOrderController::class, 'syncGhnTicketList'])->name('orders.sync-ghn-ticket-list');
    Route::post('orders/{order}/sync-ghn-ticket', [AdminOrderController::class, 'syncGhnTicket'])->name('orders.sync-ghn-ticket');
    Route::post('orders/{order}/create-ghn-ticket', [AdminOrderController::class, 'createGhnTicket'])->name('orders.create-ghn-ticket');
    Route::post('orders/{order}/reply-ghn-ticket', [AdminOrderController::class, 'replyGhnTicket'])->name('orders.reply-ghn-ticket');
    Route::get('orders/{order}/get-ghn-ticket', [AdminOrderController::class, 'getGhnTicket'])->name('orders.get-ghn-ticket');

    // Carts
    // Các route tĩnh phải khai báo trước resource để không bị trùng với `carts/{cart}`
    Route::get('carts/create-order', [AdminCartController::class, 'createOrderIndex'])->name('carts.create-order.index');
    Route::get('carts/{cart}/create-order', [AdminCartController::class, 'createOrder'])->name('carts.create-order');
    Route::post('carts/{cart}/store-order', [AdminCartController::class, 'storeOrder'])->name('carts.store-order');

    Route::resource('carts', AdminCartController::class)->except(['create', 'store']);
    Route::post('carts/{cart}/recalculate', [AdminCartController::class, 'recalculate'])->name('carts.recalculate');

    // Flash Sale Management
    Route::resource('flash-sales', \App\Http\Controllers\Admins\FlashSaleController::class);
    Route::post('flash-sales/{flashSale}/publish', [\App\Http\Controllers\Admins\FlashSaleController::class, 'publish'])->name('flash-sales.publish');
    Route::post('flash-sales/{flashSale}/toggle-active', [\App\Http\Controllers\Admins\FlashSaleController::class, 'toggleActive'])->name('flash-sales.toggle-active');
    Route::post('flash-sales/{flashSale}/duplicate', [\App\Http\Controllers\Admins\FlashSaleController::class, 'duplicate'])->name('flash-sales.duplicate');
    Route::get('flash-sales/{flashSale}/stats', [\App\Http\Controllers\Admins\FlashSaleController::class, 'stats'])->name('flash-sales.stats');
    Route::get('flash-sales/{flashSale}/preview', [\App\Http\Controllers\Admins\FlashSaleController::class, 'preview'])->name('flash-sales.preview');
    Route::get('flash-sales/{flashSale}/items', [\App\Http\Controllers\Admins\FlashSaleController::class, 'items'])->name('flash-sales.items');
    Route::post('flash-sales/{flashSale}/items/add', [\App\Http\Controllers\Admins\FlashSaleController::class, 'addItem'])->name('flash-sales.items.add');
    Route::post('flash-sales/{flashSale}/items/add-by-categories', [\App\Http\Controllers\Admins\FlashSaleController::class, 'addItemsByCategories'])->name('flash-sales.items.add-by-categories');
    Route::post('flash-sales/{flashSale}/items/import-excel', [\App\Http\Controllers\Admins\FlashSaleController::class, 'importItemsFromExcel'])->name('flash-sales.items.import-excel');
    Route::get('flash-sales/{flashSale}/items/download-template', [\App\Http\Controllers\Admins\FlashSaleController::class, 'downloadImportTemplate'])->name('flash-sales.items.download-template');
    Route::post('flash-sales/{flashSale}/items/bulk-action', [\App\Http\Controllers\Admins\FlashSaleController::class, 'bulkActionItems'])->name('flash-sales.items.bulk-action');
    Route::put('flash-sales/{flashSale}/items/{item}', [\App\Http\Controllers\Admins\FlashSaleController::class, 'updateItem'])->name('flash-sales.items.update');
    Route::delete('flash-sales/{flashSale}/items/{item}', [\App\Http\Controllers\Admins\FlashSaleController::class, 'deleteItem'])->name('flash-sales.items.delete');
    Route::delete('flash-sales/{flashSale}/items', [\App\Http\Controllers\Admins\FlashSaleController::class, 'deleteAllItems'])->name('flash-sales.items.delete-all');
    Route::get('flash-sales/{flashSale}/items/search-products', [\App\Http\Controllers\Admins\FlashSaleController::class, 'searchProducts'])->name('flash-sales.items.search-products');
    Route::get('flash-sales/{flashSale}/items/products-by-category', [\App\Http\Controllers\Admins\FlashSaleController::class, 'productsByCategory'])->name('flash-sales.items.products-by-category');
    Route::get('flash-sales/{flashSale}/items/suggest-best-selling', [\App\Http\Controllers\Admins\FlashSaleController::class, 'suggestBestSellingProducts'])->name('flash-sales.items.suggest-best-selling');
    Route::get('flash-sales/{flashSale}/items/{item}/price-logs', [\App\Http\Controllers\Admins\FlashSaleController::class, 'priceLogs'])->name('flash-sales.items.price-logs');
    Route::get('flash-sales/{flashSale}/revenue-by-time', [\App\Http\Controllers\Admins\FlashSaleController::class, 'revenueByTime'])->name('flash-sales.revenue-by-time');
    Route::get('flash-sales/{flashSale}/conversion-metrics', [\App\Http\Controllers\Admins\FlashSaleController::class, 'conversionMetrics'])->name('flash-sales.conversion-metrics');
    Route::get('flash-sales/{flashSale}/sales-heatmap', [\App\Http\Controllers\Admins\FlashSaleController::class, 'salesHeatmap'])->name('flash-sales.sales-heatmap');
    Route::get('flash-sales/compare', [\App\Http\Controllers\Admins\FlashSaleController::class, 'compare'])->name('flash-sales.compare');

    // Post Management
    Route::resource('posts', \App\Http\Controllers\Admins\PostController::class);
    Route::post('posts/upload-image', [\App\Http\Controllers\Admins\PostController::class, 'uploadImage'])->name('posts.upload-image');
    Route::post('posts/{post}/publish', [\App\Http\Controllers\Admins\PostController::class, 'publish'])->name('posts.publish');
    Route::post('posts/{post}/archive', [\App\Http\Controllers\Admins\PostController::class, 'archive'])->name('posts.archive');
    Route::post('posts/{post}/duplicate', [\App\Http\Controllers\Admins\PostController::class, 'duplicate'])->name('posts.duplicate');
    Route::post('posts/{post}/feature', [\App\Http\Controllers\Admins\PostController::class, 'feature'])->name('posts.feature');
    Route::post('posts/{post}/unfeature', [\App\Http\Controllers\Admins\PostController::class, 'unfeature'])->name('posts.unfeature');
    Route::post('posts/{post}/restore', [\App\Http\Controllers\Admins\PostController::class, 'restore'])->name('posts.restore');
    Route::get('posts/{post}/revisions', [\App\Http\Controllers\Admins\PostController::class, 'revisions'])->name('posts.revisions');
    Route::post('posts/{post}/autosave', [\App\Http\Controllers\Admins\PostController::class, 'autosave'])->name('posts.autosave');
    Route::post('posts/{post}/restore-revision/{revision}', [\App\Http\Controllers\Admins\PostController::class, 'restoreRevision'])->name('posts.restore-revision');

    // Media Management
    Route::prefix('media')->name('media.')->group(function () {
        Route::get('/', [MediaController::class, 'index'])->name('index');
        Route::get('/list', [MediaController::class, 'list'])->name('list');
        Route::get('/folder-tree', [MediaController::class, 'folderTree'])->name('folder-tree');
        Route::get('/search', [MediaController::class, 'search'])->name('search');
        Route::get('/info', [MediaController::class, 'info'])->name('info');
        Route::post('/upload', [MediaController::class, 'upload'])->name('upload');
        Route::post('/rename', [MediaController::class, 'rename'])->name('rename');
        Route::post('/move', [MediaController::class, 'move'])->name('move');
        Route::post('/copy', [MediaController::class, 'copy'])->name('copy');
        Route::post('/delete', [MediaController::class, 'delete'])->name('delete');
        Route::post('/bulk-delete', [MediaController::class, 'bulkDelete'])->name('bulk-delete');
        Route::post('/update-meta', [MediaController::class, 'updateMeta'])->name('update-meta');
        Route::post('/folder/create', [MediaController::class, 'createFolder'])->name('folder.create');
        Route::post('/folder/rename', [MediaController::class, 'renameFolder'])->name('folder.rename');
        Route::post('/folder/delete', [MediaController::class, 'deleteFolder'])->name('folder.delete');
    });

    // Voucher Management
    // Các route tĩnh phải khai báo trước resource để không bị trùng với `vouchers/{voucher}`
    Route::get('vouchers/analytics', [\App\Http\Controllers\Admins\VoucherAnalyticsController::class, 'dashboard'])->name('vouchers.analytics');
    Route::get('vouchers/analytics/{voucher}', [\App\Http\Controllers\Admins\VoucherAnalyticsController::class, 'voucherDetail'])->name('vouchers.analytics.detail');
    Route::post('vouchers/test', [\App\Http\Controllers\Admins\VoucherController::class, 'test'])->name('vouchers.test');
    Route::post('vouchers/upload-image', [\App\Http\Controllers\Admins\VoucherController::class, 'uploadImage'])->name('vouchers.upload-image');

    Route::resource('vouchers', \App\Http\Controllers\Admins\VoucherController::class);
    Route::post('vouchers/{voucher}/toggle', [\App\Http\Controllers\Admins\VoucherController::class, 'toggle'])->name('vouchers.toggle');
    Route::post('vouchers/{voucher}/duplicate', [\App\Http\Controllers\Admins\VoucherController::class, 'duplicate'])->name('vouchers.duplicate');
    Route::post('vouchers/{voucher}/restore', [\App\Http\Controllers\Admins\VoucherController::class, 'restore'])->name('vouchers.restore');
    Route::get('vouchers/{voucher}/products', [\App\Http\Controllers\Admins\VoucherController::class, 'getProducts'])->name('vouchers.products');

    // Comments Management
    Route::get('comments', [\App\Http\Controllers\Admins\AdminCommentController::class, 'index'])->name('comments.index');
    Route::get('comments/{id}', [\App\Http\Controllers\Admins\AdminCommentController::class, 'show'])->name('comments.show');
    Route::post('comments/{id}/approve', [\App\Http\Controllers\Admins\AdminCommentController::class, 'approve'])->name('comments.approve');
    Route::post('comments/{id}/reject', [\App\Http\Controllers\Admins\AdminCommentController::class, 'reject'])->name('comments.reject');
    Route::post('comments/{id}/reply', [\App\Http\Controllers\Admins\AdminCommentController::class, 'reply'])->name('comments.reply');
    Route::post('comments/replies/{id}', [\App\Http\Controllers\Admins\AdminCommentController::class, 'updateReply'])->name('comments.replies.update');
    Route::delete('comments/replies/{id}', [\App\Http\Controllers\Admins\AdminCommentController::class, 'deleteReply'])->name('comments.replies.delete');
    Route::delete('comments/{id}', [\App\Http\Controllers\Admins\AdminCommentController::class, 'destroy'])->name('comments.destroy');

    // Tags Management
    Route::resource('tags', \App\Http\Controllers\Admins\TagController::class)->except(['show']);
    Route::post('tags/bulk-delete', [\App\Http\Controllers\Admins\TagController::class, 'destroyMultiple'])->name('tags.bulk-delete');
    Route::post('tags/merge', [\App\Http\Controllers\Admins\TagController::class, 'merge'])->name('tags.merge');
    Route::get('tags/suggest', [\App\Http\Controllers\Admins\TagController::class, 'suggest'])->name('tags.suggest');
    Route::post('tags/suggest-from-content', [\App\Http\Controllers\Admins\TagController::class, 'suggestFromContent'])->name('tags.suggest-from-content');
    Route::get('tags/entities', [\App\Http\Controllers\Admins\TagController::class, 'getEntities'])->name('tags.entities');

    // Settings Management
    Route::resource('settings', \App\Http\Controllers\Admins\SettingController::class);

    // Banners Management
    Route::resource('banners', \App\Http\Controllers\Admins\BannerController::class);
    Route::patch('banners/{banner}/toggle', [\App\Http\Controllers\Admins\BannerController::class, 'toggle'])->name('banners.toggle');

    // Sitemap Management
    Route::prefix('sitemap')->name('sitemap.')->group(function () {
        Route::get('/', [SitemapController::class, 'index'])->name('index');
        Route::post('/config', [SitemapController::class, 'updateConfig'])->name('config.update');
        Route::post('/rebuild', [SitemapController::class, 'rebuild'])->name('rebuild');
        Route::post('/clear-cache', [SitemapController::class, 'clearCache'])->name('clear-cache');
        Route::post('/ping', [SitemapController::class, 'ping'])->name('ping');
        Route::get('/preview', [SitemapController::class, 'preview'])->name('preview');

        Route::post('/excludes', [SitemapController::class, 'storeExclude'])->name('excludes.store');
        Route::patch('/excludes/{id}/toggle', [SitemapController::class, 'toggleExclude'])->name('excludes.toggle');
        Route::delete('/excludes/{id}', [SitemapController::class, 'deleteExclude'])->name('excludes.delete');
    });

    // Contacts Management
    Route::resource('contacts', AdminContactController::class)->only(['index', 'show', 'destroy']);
    Route::post('contacts/{contact}/status', [AdminContactController::class, 'updateStatus'])->name('contacts.update-status');
    Route::post('contacts/{contact}/note', [AdminContactController::class, 'updateNote'])->name('contacts.update-note');
    Route::post('contacts/{contact}/reply', [AdminContactController::class, 'reply'])->name('contacts.reply');
    Route::get('contacts/{contact}/attachment', [AdminContactController::class, 'downloadAttachment'])->name('contacts.attachment');
    Route::post('contacts/editor/upload-image', [AdminContactController::class, 'uploadEditorImage'])->name('contacts.editor.upload-image');
    Route::post('contacts/bulk-action', [AdminContactController::class, 'bulkAction'])->name('contacts.bulk-action');
    Route::post('contacts/{id}/restore', [AdminContactController::class, 'restore'])->name('contacts.restore');

    // Addresses Management
    Route::resource('addresses', \App\Http\Controllers\Admins\AddressController::class)
        ->only(['index', 'show', 'edit', 'update', 'destroy']);
    Route::post('addresses/{address}/set-default', [\App\Http\Controllers\Admins\AddressController::class, 'setDefault'])
        ->name('addresses.set-default');
    Route::get('addresses/search/accounts', [\App\Http\Controllers\Admins\AddressController::class, 'searchAccounts'])
        ->name('addresses.search.accounts');
    Route::get('addresses/search/provinces', [\App\Http\Controllers\Admins\AddressController::class, 'searchProvinces'])
        ->name('addresses.search.provinces');
    Route::get('addresses/search/districts', [\App\Http\Controllers\Admins\AddressController::class, 'searchDistricts'])
        ->name('addresses.search.districts');

    // Newsletters Management
    Route::get('newsletters/campaign', [AdminNewsletterController::class, 'showCampaignForm'])->name('newsletters.campaign');
    Route::post('newsletters/send-bulk', [AdminNewsletterController::class, 'sendBulkEmail'])->name('newsletters.send-bulk');
    Route::get('newsletters/campaigns', [AdminNewsletterController::class, 'campaignsIndex'])->name('newsletters.campaigns.index');
    Route::get('newsletters/campaigns/{id}', [AdminNewsletterController::class, 'campaignsShow'])->name('newsletters.campaigns.show');
    Route::get('newsletters', [AdminNewsletterController::class, 'index'])->name('newsletters.index');
    Route::get('newsletters/{id}', [AdminNewsletterController::class, 'show'])->name('newsletters.show');
    Route::delete('newsletters/{id}', [AdminNewsletterController::class, 'destroy'])->name('newsletters.destroy');
    Route::post('newsletters/{id}/change-status', [AdminNewsletterController::class, 'changeStatus'])->name('newsletters.change-status');
    Route::post('newsletters/{id}/resend-verify', [AdminNewsletterController::class, 'resendVerifyEmail'])->name('newsletters.resend-verify');

    // Notifications Management
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admins\AdminNotificationController::class, 'index'])->name('index');
        Route::post('{id}/read', [\App\Http\Controllers\Admins\AdminNotificationController::class, 'markAsRead'])->name('read');
        Route::post('read-all', [\App\Http\Controllers\Admins\AdminNotificationController::class, 'markAllAsRead'])->name('read-all');
        Route::delete('{id}', [\App\Http\Controllers\Admins\AdminNotificationController::class, 'destroy'])->name('destroy');
        Route::delete('read/delete', [\App\Http\Controllers\Admins\AdminNotificationController::class, 'deleteRead'])->name('delete-read');
        Route::get('unread-count', [\App\Http\Controllers\Admins\AdminNotificationController::class, 'unreadCount'])->name('unread-count');
        Route::get('latest', [\App\Http\Controllers\Admins\AdminNotificationController::class, 'latest'])->name('latest');
    });

    // Affiliates Management
    Route::resource('affiliates', \App\Http\Controllers\Admins\AffiliateController::class)->except(['show', 'create', 'edit']);
    Route::post('affiliates', [\App\Http\Controllers\Admins\AffiliateController::class, 'store'])->name('affiliates.store');
    Route::patch('affiliates/{affiliate}', [\App\Http\Controllers\Admins\AffiliateController::class, 'update'])->name('affiliates.update');

    // Reports & Analytics
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admins\ReportController::class, 'index'])->name('index');
        Route::get('/revenue', [\App\Http\Controllers\Admins\ReportController::class, 'revenue'])->name('revenue');
        Route::get('/products', [\App\Http\Controllers\Admins\ReportController::class, 'products'])->name('products');
        Route::get('/customers', [\App\Http\Controllers\Admins\ReportController::class, 'customers'])->name('customers');
        Route::get('/inventory', [\App\Http\Controllers\Admins\ReportController::class, 'inventory'])->name('inventory');
        Route::post('/export', [\App\Http\Controllers\Admins\ReportController::class, 'export'])->name('export');
    });

    // Orders Export
    Route::get('orders/export/excel', [AdminOrderController::class, 'export'])->name('orders.export');

    // Contacts Export
    Route::get('contacts/export/excel', [AdminContactController::class, 'export'])->name('contacts.export');

    // Email Templates Management
    Route::resource('email-templates', \App\Http\Controllers\Admins\EmailTemplateController::class)->except(['show']);
    Route::post('email-templates/{emailTemplate}/toggle', [\App\Http\Controllers\Admins\EmailTemplateController::class, 'toggle'])->name('email-templates.toggle');

    // Backup & Restore
    Route::resource('backups', \App\Http\Controllers\Admins\BackupController::class)->only(['index', 'store', 'destroy']);
    Route::get('backups/{fileName}/download', [\App\Http\Controllers\Admins\BackupController::class, 'download'])->name('backups.download');
    Route::post('backups/{fileName}/restore', [\App\Http\Controllers\Admins\BackupController::class, 'restore'])->name('backups.restore');

    // Trash Management (Thùng rác)
    Route::prefix('trash')->name('trash.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admins\TrashController::class, 'index'])->name('index');
        Route::patch('{type}/{id}/restore', [\App\Http\Controllers\Admins\TrashController::class, 'restore'])->name('restore');
        Route::delete('{type}/{id}', [\App\Http\Controllers\Admins\TrashController::class, 'forceDelete'])->name('force-delete');
        Route::post('{type}/restore-all', [\App\Http\Controllers\Admins\TrashController::class, 'restoreAll'])->name('restore-all');
        Route::delete('{type}/empty', [\App\Http\Controllers\Admins\TrashController::class, 'forceDeleteAll'])->name('empty');
        Route::post('bulk-restore', [\App\Http\Controllers\Admins\TrashController::class, 'bulkRestore'])->name('bulk-restore');
        Route::post('bulk-delete', [\App\Http\Controllers\Admins\TrashController::class, 'bulkForceDelete'])->name('bulk-delete');
    });
});

Route::fallback(fn () => response()->view('clients.pages.errors.404', [], 404));
