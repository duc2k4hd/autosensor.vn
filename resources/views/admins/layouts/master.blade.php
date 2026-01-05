<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel') | {{ $settings->site_name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="{{ asset('admins/css/custom.css') }}">
    @stack('styles')
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f7fa;
            color: #333;
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 260px;
            background: #f8f9fa;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 1px 0 3px rgba(0,0,0,0.05);
            z-index: 1000;
            border-right: 1px solid #e9ecef;
            transition: transform 0.3s ease, width 0.3s ease;
        }
        .sidebar.collapsed {
            transform: translateX(-100%);
        }
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        .sidebar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        .sidebar::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 3px;
        }
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            background: white;
        }
        .sidebar-header img {
            max-width: 100%;
            height: auto;
            margin-bottom: 10px;
        }
        .sidebar-header h1 {
            font-size: 18px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 5px;
            color: #333;
        }
        .sidebar-header p {
            font-size: 12px;
            color: #6c757d;
        }
        .sidebar-menu {
            padding: 10px 0;
        }
        .menu-section {
            padding: 8px 15px;
            font-size: 11px;
            text-transform: uppercase;
            color: #6c757d;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-top: 5px;
        }
        .menu-group {
            margin-bottom: 2px;
        }
        .menu-group-header {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            color: #495057;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.2s;
            font-size: 14px;
            font-weight: 500;
            user-select: none;
        }
        .menu-group-header:hover {
            background: #e9ecef;
        }
        .menu-group-header.active {
            background: #dee2e6;
            color: #212529;
        }
        .menu-group-header .menu-arrow {
            margin-left: auto;
            font-size: 12px;
            transition: transform 0.2s;
            color: #6c757d;
        }
        .menu-group-header.expanded .menu-arrow {
            transform: rotate(90deg);
        }
        .menu-group-items {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            background: white;
        }
        .menu-group-items.expanded {
            max-height: 1000px;
        }
        .menu-item {
            display: flex;
            align-items: center;
            padding: 8px 15px 8px 40px;
            color: #6c757d;
            text-decoration: none;
            transition: all 0.2s;
            font-size: 13px;
            font-weight: 400;
        }
        .menu-item:hover {
            background: #f1f3f5;
            color: #212529;
        }
        .menu-item.active {
            background: #e7f5ff;
            color: #1971c2;
            font-weight: 500;
            border-left: 3px solid #1971c2;
        }
        .menu-item-icon {
            display: inline-block;
            width: 18px;
            margin-right: 10px;
            text-align: center;
            font-size: 14px;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 20px;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }
        .main-content.sidebar-collapsed {
            margin-left: 0;
        }
        
        /* Top Bar */
        .top-bar {
            background: white;
            padding: 15px 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .top-bar-title {
            font-size: 24px;
            font-weight: 600;
            color: #333;
        }
        .top-bar-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        /* Content Area */
        .content-area {
            background: transparent;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0 !important;
            }
            .main-content.sidebar-collapsed {
                margin-left: 0 !important;
            }
        }
        .menu-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f3f5;
            color: #495057;
            border: 1px solid #dee2e6;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 18px;
            transition: all 0.2s;
            margin-right: 15px;
        }
        .menu-toggle:hover {
            background: #e9ecef;
            border-color: #ced4da;
        }
        
        /* Pagination Container */
        nav[role="navigation"] {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        /* Mobile pagination buttons */
        nav[role="navigation"] > div:first-child {
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }
        
        nav[role="navigation"] > div:first-child a,
        nav[role="navigation"] > div:first-child span {
            padding: 10px 20px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #fff;
            color: #475569;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
        }
        
        nav[role="navigation"] > div:first-child a:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #334155;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        nav[role="navigation"] > div:first-child span.cursor-default {
            opacity: 0.5;
            cursor: not-allowed;
            background: #f8f9fa;
        }
        
        /* Desktop pagination container */
        nav[role="navigation"] > div:last-child {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        /* Showing text */
        nav[role="navigation"] p.text-sm {
            color: #64748b;
            font-size: 14px;
            font-weight: 400;
            margin: 0;
        }
        
        nav[role="navigation"] p.text-sm .font-medium {
            color: #334155;
            font-weight: 600;
        }
        
        /* Pagination buttons container - Target by multiple attributes */
        nav[role="navigation"] span.relative.z-0 {
            display: inline-flex;
            align-items: center;
            gap: 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        /* All pagination buttons */
        nav[role="navigation"] span.relative.z-0 > * {
            margin: 0;
        }
        
        nav[role="navigation"] span.relative.z-0 a,
        nav[role="navigation"] span.relative.z-0 span {
            padding: 10px 16px;
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #475569;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            height: 40px;
            border-right: none;
        }
        
        nav[role="navigation"] span.relative.z-0 > *:first-child {
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
        }
        
        nav[role="navigation"] span.relative.z-0 > *:last-child {
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
            border-right: 1px solid #e2e8f0;
        }
        
        /* Hover states */
        nav[role="navigation"] span.relative.z-0 a:hover {
            background: #f1f5f9;
            color: #334155;
            z-index: 1;
            border-color: #cbd5e1;
        }
        
        /* Active page - Beautiful gradient */
        nav[role="navigation"] span[aria-current="page"] span,
        nav[role="navigation"] span.cursor-default:not([aria-disabled]) {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%) !important;
            color: #fff !important;
            border-color: #6366f1 !important;
            font-weight: 600;
            z-index: 2;
            box-shadow: 0 2px 4px rgba(99, 102, 241, 0.3);
        }
        
        /* Disabled buttons */
        nav[role="navigation"] span[aria-disabled="true"] span {
            opacity: 0.4;
            cursor: not-allowed;
            background: #f8f9fa !important;
            color: #94a3b8 !important;
        }
        
        /* SVG icons in buttons */
        nav[role="navigation"] svg {
            width: 20px;
            height: 20px;
        }
        
        /* Focus states for accessibility */
        nav[role="navigation"] a:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            z-index: 1;
        }

        .tox-tinymce {
            min-height: 500px;
        }
        
        /* Notification Bell */
        .notification-bell {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            transition: all 0.2s;
            text-decoration: none;
        }
        .notification-bell:hover {
            background: #e9ecef;
            border-color: #ced4da;
            transform: scale(1.05);
        }
        .notification-badge {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }
        
        /* Responsive design */
        @media (max-width: 640px) {
            nav[role="navigation"] > div:last-child {
                flex-direction: column;
                align-items: stretch;
            }
            
            nav[role="navigation"] span.relative.z-0 {
                justify-content: center;
                flex-wrap: wrap;
            }
            
            nav[role="navigation"] p.text-sm {
                text-align: center;
                width: 100%;
            }
        }
    </style>
    <style>
        .media-picker-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 8px;
            min-height: 360px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .media-picker-card {
            background: #fff;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            transition: all .15s ease;
            position: relative;
        }
        .media-picker-card:hover {
            border-color: #3b82f6;
            box-shadow: 0 2px 8px rgba(59,130,246,0.15);
        }
        .media-picker-card.selected {
            border-color: #3b82f6;
            background: #eff6ff;
        }
        .media-picker-card {
            position: relative;
        }
        .media-picker-checkbox-wrapper {
            position: absolute;
            top: 8px;
            left: 8px;
            z-index: 10;
            background: rgba(255,255,255,0.9);
            border-radius: 4px;
            padding: 2px;
        }
        .media-picker-context-menu {
            position: fixed;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            min-width: 180px;
            padding: 4px 0;
            display: none;
        }
        .media-picker-context-menu-item {
            padding: 8px 16px;
            cursor: pointer;
            font-size: 14px;
            color: #334155;
            transition: background 0.15s;
        }
        .media-picker-context-menu-item:hover {
            background: #f1f5f9;
        }
        .media-picker-context-menu-divider {
            height: 1px;
            background: #e2e8f0;
            margin: 4px 0;
        }
        .media-picker-context-menu-item[data-action="delete"] {
            color: #dc2626;
        }
        .media-picker-context-menu-item[data-action="delete"]:hover {
            background: #fee2e2;
        }
        .media-picker-thumb {
            width: 100%;
            height: 100px;
            position: relative;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .media-picker-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .media-picker-meta {
            padding: 6px 8px;
        }
        .media-picker-name {
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.3;
        }
        .media-picker-size {
            font-size: 10px;
            color: #64748b;
            margin-top: 2px;
        }
        .media-picker-toolbar input {
            min-width: 220px;
        }
    </style>
    @stack('head')
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div><img src="{{ asset('clients/assets/img/business/'. $settings->site_logo) }}" alt="{{ $settings->site_name }}" style="width: 100%; height: 100%;"></div>
            {{-- <h1>📊 Admin Panel</h1>
            <p style="color: white; text-align: center;">{{ $settings->site_name }}</p> --}}
        </div>
        <nav class="sidebar-menu">
            <div class="menu-section">Tổng Quan</div>
            <a href="{{ route('admin.dashboard') }}" class="menu-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <span class="menu-item-icon">📊</span>
                Dashboard
            </a>
            
            <div class="menu-section">Sản Phẩm</div>
            <div class="menu-group">
                <div class="menu-group-header" data-group="products">
                    <span class="menu-item-icon">📦</span>
                    <span>Sản Phẩm</span>
                    <span class="menu-arrow">▶</span>
                </div>
                <div class="menu-group-items" id="products-group">
                    @if(Route::has('admin.products.index'))
                        <a href="{{ route('admin.products.index') }}" class="menu-item {{ request()->routeIs('admin.products.index') && !request('status') ? 'active' : '' }}">
                            Tất cả sản phẩm
                        </a>
                        <a href="{{ route('admin.products.index', ['status' => 'active']) }}" class="menu-item {{ request()->routeIs('admin.products.index') && request('status') === 'active' ? 'active' : '' }}">
                            Đang bán
                        </a>
                        <a href="{{ route('admin.products.index', ['status' => 'inactive']) }}" class="menu-item {{ request()->routeIs('admin.products.index') && request('status') === 'inactive' ? 'active' : '' }}">
                            Tạm ẩn
                        </a>
                        @if(Route::has('admin.products.import-excel'))
                            <a href="{{ route('admin.products.import-excel') }}" class="menu-item {{ request()->routeIs('admin.products.import-excel*') ? 'active' : '' }}">
                                Nhập Excel
                            </a>
                        @endif
                        @if(Route::has('admin.products.export-excel'))
                            <a href="{{ route('admin.products.export-excel') }}" class="menu-item {{ request()->routeIs('admin.products.export-excel') ? 'active' : '' }}">
                                Xuất Excel
                            </a>
                        @endif
                    @else
                        <div class="menu-item" style="color: #999; cursor: not-allowed;">
                            Sản phẩm (Chưa khả dụng)
                        </div>
                    @endif
                </div>
            </div>
            @if(Route::has('admin.categories.index'))
                <a href="{{ route('admin.categories.index') }}" class="menu-item {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                    <span class="menu-item-icon">🏷️</span>
                    Danh Mục
                </a>
            @endif
            @if(Route::has('admin.flash-sales.index'))
                <a href="{{ route('admin.flash-sales.index') }}" class="menu-item {{ request()->routeIs('admin.flash-sales.*') ? 'active' : '' }}">
                    <span class="menu-item-icon">⚡</span>
                    Flash Sale
                </a>
            @endif
            
            <div class="menu-section">Nội Dung</div>
            @if(Route::has('admin.posts.index'))
                <a href="{{ route('admin.posts.index') }}" class="menu-item {{ request()->routeIs('admin.posts.*') ? 'active' : '' }}">
                    <span class="menu-item-icon">📝</span>
                    Bài viết
                </a>
            @endif
            @if(Route::has('admin.comments.index'))
                <a href="{{ route('admin.comments.index') }}" class="menu-item {{ request()->routeIs('admin.comments.*') ? 'active' : '' }}">
                    <span class="menu-item-icon">💬</span>
                    Bình luận
                </a>
            @endif
            @if(Route::has('admin.email-accounts.index'))
                <a href="{{ route('admin.email-accounts.index') }}" class="menu-item {{ request()->routeIs('admin.email-accounts.*') ? 'active' : '' }}">
                    <span class="menu-item-icon">📧</span>
                    Email
                </a>
            @endif
            @if(Route::has('admin.tags.index'))
                <a href="{{ route('admin.tags.index') }}" class="menu-item {{ request()->routeIs('admin.tags.*') ? 'active' : '' }}">
                    <span class="menu-item-icon">🏷️</span>
                    Thẻ (Tags)
                </a>
            @endif
            @if(Route::has('admin.banners.index'))
                <a href="{{ route('admin.banners.index') }}" class="menu-item {{ request()->routeIs('admin.banners.*') ? 'active' : '' }}">
                    <span class="menu-item-icon">🖼️</span>
                    Banner
                </a>
            @endif
            @if(Route::has('admin.media.index'))
                <a href="{{ route('admin.media.index') }}" class="menu-item {{ request()->routeIs('admin.media.*') ? 'active' : '' }}">
                    <span class="menu-item-icon">🗂️</span>
                    Media
                </a>
            @endif
            @if(Route::has('admin.sitemap.index'))
                <a href="{{ route('admin.sitemap.index') }}" class="menu-item {{ request()->routeIs('admin.sitemap.*') ? 'active' : '' }}">
                    <span class="menu-item-icon">🗺️</span>
                    Sitemap
                </a>
            @endif
            
            <div class="menu-section">Đơn Hàng</div>
            @if(Route::has('admin.orders.index'))
                <div class="menu-group">
                    <div class="menu-group-header" data-group="orders">
                        <span class="menu-item-icon">📋</span>
                        <span>Đơn Hàng</span>
                        <span class="menu-arrow">▶</span>
                    </div>
                    <div class="menu-group-items" id="orders-group">
                        <a href="{{ route('admin.orders.index') }}" class="menu-item {{ request()->routeIs('admin.orders.index') && !request('status') && !request('delivery_status') ? 'active' : '' }}">
                            Tất cả đơn hàng
                        </a>
                        <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="menu-item {{ request()->routeIs('admin.orders.*') && request('status') === 'pending' ? 'active' : '' }}">
                            Chờ xử lý
                        </a>
                        <a href="{{ route('admin.orders.index', ['delivery_status' => 'shipped']) }}" class="menu-item {{ request()->routeIs('admin.orders.*') && request('delivery_status') === 'shipped' ? 'active' : '' }}">
                            Đang giao hàng
                        </a>
                        <a href="{{ route('admin.orders.index', ['status' => 'completed']) }}" class="menu-item {{ request()->routeIs('admin.orders.*') && request('status') === 'completed' ? 'active' : '' }}">
                            Hoàn thành
                        </a>
                    </div>
                </div>
            @endif
            @if(Route::has('admin.carts.index'))
                <a href="{{ route('admin.carts.index') }}" class="menu-item {{ request()->routeIs('admin.carts.index') || (request()->routeIs('admin.carts.show') || request()->routeIs('admin.carts.edit')) ? 'active' : '' }}">
                    <span class="menu-item-icon">🛒</span>
                    Giỏ Hàng
                </a>
            @endif
            @if(Route::has('admin.carts.create-order.index'))
                <a href="{{ route('admin.carts.create-order.index') }}" class="menu-item {{ request()->routeIs('admin.carts.create-order.*') ? 'active' : '' }}">
                    <span class="menu-item-icon">📦</span>
                    Lên Đơn Hàng
                </a>
            @endif
            
            <div class="menu-section">Khách Hàng</div>
            @if(Route::has('admin.accounts.index'))
                <a href="{{ route('admin.accounts.index') }}" class="menu-item {{ request()->routeIs('admin.accounts.*') ? 'active' : '' }}">
                    <span class="menu-item-icon">👤</span>
                    Tài khoản
                </a>
            @endif
            @if(Route::has('admin.newsletters.index'))
                <a href="{{ route('admin.newsletters.index') }}" class="menu-item {{ request()->routeIs('admin.newsletters.*') ? 'active' : '' }}">
                    <span class="menu-item-icon">📧</span>
                    <span style="flex: 1;">Newsletter</span>
                    @php
                        try {
                            $pendingNewsletterCount = \App\Models\Newsletter::where('is_verified', false)->count();
                        } catch (\Exception $e) {
                            $pendingNewsletterCount = 0;
                        }
                    @endphp
                    @if($pendingNewsletterCount > 0)
                        <span style="background: #f59e0b; color: white; padding: 2px 6px; border-radius: 10px; font-size: 10px;">{{ $pendingNewsletterCount }}</span>
                    @endif
                </a>
            @endif
            @if(Route::has('admin.contacts.index'))
                <a href="{{ route('admin.contacts.index') }}" class="menu-item {{ request()->routeIs('admin.contacts.*') ? 'active' : '' }}">
                    <span class="menu-item-icon">💬</span>
                    <span style="flex: 1;">Liên Hệ</span>
                    @php
                        try {
                            $newContactCount = \App\Models\Contact::where('status', 'new')->count();
                        } catch (\Exception $e) {
                            $newContactCount = 0;
                        }
                    @endphp
                    @if($newContactCount > 0)
                        <span style="background: #ef4444; color: white; padding: 2px 6px; border-radius: 10px; font-size: 10px; font-weight: 600;">{{ $newContactCount }}</span>
                    @endif
                </a>
            @endif
            @if(Route::has('admin.notifications.index'))
                <a href="{{ route('admin.notifications.index') }}" class="menu-item {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}">
                    <span class="menu-item-icon">🔔</span>
                    Thông báo
                </a>
            @endif
            @if(Route::has('admin.addresses.index'))
                <a href="{{ route('admin.addresses.index') }}" class="menu-item {{ request()->routeIs('admin.addresses.*') ? 'active' : '' }}">
                    <span class="menu-item-icon">📍</span>
                    Địa Chỉ Giao Hàng
                </a>
            @endif
            
            <div class="menu-section">Khuyến Mãi</div>
            @if(Route::has('admin.vouchers.index'))
                <div class="menu-group">
                    <div class="menu-group-header" data-group="vouchers">
                        <span class="menu-item-icon">🎫</span>
                        <span>Voucher</span>
                        <span class="menu-arrow">▶</span>
                    </div>
                    <div class="menu-group-items" id="vouchers-group">
                        <a href="{{ route('admin.vouchers.index') }}" class="menu-item {{ request()->routeIs('admin.vouchers.index') ? 'active' : '' }}">
                            Tất cả voucher
                        </a>
                        <a href="{{ route('admin.vouchers.create') }}" class="menu-item {{ request()->routeIs('admin.vouchers.create') ? 'active' : '' }}">
                            Tạo voucher mới
                        </a>
                        @if(Route::has('admin.vouchers.analytics'))
                            <a href="{{ route('admin.vouchers.analytics') }}" class="menu-item {{ request()->routeIs('admin.vouchers.analytics*') ? 'active' : '' }}">
                                Analytics & Báo cáo
                            </a>
                        @endif
                    </div>
                </div>
            @endif
            
            <div class="menu-section">Báo Cáo & Phân Tích</div>
            @if(Route::has('admin.reports.index'))
                <a href="{{ route('admin.reports.index') }}" class="menu-item {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                    <span class="menu-item-icon">📊</span>
                    Báo Cáo
                </a>
            @endif
            @if(Route::has('admin.affiliates.index'))
                <a href="{{ route('admin.affiliates.index') }}" class="menu-item {{ request()->routeIs('admin.affiliates.*') ? 'active' : '' }}">
                    <span class="menu-item-icon">🔗</span>
                    Affiliate
                </a>
            @endif
            
            <div class="menu-section">Hệ Thống</div>
            @if(Route::has('admin.email-templates.index'))
                <a href="{{ route('admin.email-templates.index') }}" class="menu-item {{ request()->routeIs('admin.email-templates.*') ? 'active' : '' }}">
                    <span class="menu-item-icon">📧</span>
                    Email Templates
                </a>
            @endif
            @if(Route::has('admin.backups.index'))
                <a href="{{ route('admin.backups.index') }}" class="menu-item {{ request()->routeIs('admin.backups.*') ? 'active' : '' }}">
                    <span class="menu-item-icon">💾</span>
                    Backup & Restore
                </a>
            @endif
            @if(Route::has('admin.settings.index'))
                <a href="{{ route('admin.settings.index') }}" class="menu-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                    <span class="menu-item-icon">⚙️</span>
                    Cài Đặt
                </a>
            @endif
            @if(Route::has('admin.trash.index'))
                <a href="{{ route('admin.trash.index') }}" class="menu-item {{ request()->routeIs('admin.trash.*') ? 'active' : '' }}">
                    <span class="menu-item-icon">🗑️</span>
                    Thùng Rác
                </a>
            @endif
            <a href="{{ url('/') }}" class="menu-item" target="_blank">
                <span class="menu-item-icon">🌐</span>
                Về Trang Chủ
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div style="display: flex; align-items: center;">
                <button class="menu-toggle" id="sidebarToggle" onclick="toggleSidebar()" title="Đóng/Mở menu">
                    <span id="toggleIcon">☰</span>
                </button>
                <span class="top-bar-title">@yield('page-title', 'Dashboard')</span>
            </div>
            <div class="top-bar-actions">
                {{-- Notification Bell --}}
                <div class="notification-bell-container" style="position: relative; margin-right: 15px;">
                    <a href="{{ route('admin.notifications.index') }}" class="notification-bell" id="notificationBell" title="Thông báo">
                        <i class="fa fa-bell" style="font-size: 20px; color: #495057;"></i>
                        <span class="notification-badge" id="notificationBadge" style="display: none; position: absolute; top: -5px; right: -5px; background: #ef4444; color: white; border-radius: 50%; width: 20px; height: 20px; font-size: 11px; font-weight: bold; display: flex; align-items: center; justify-content: center;">0</span>
                    </a>
                </div>
                
                <div class="user-info">
                    <div class="user-avatar">
                        @php
                            $user = auth('web')->user() ?? auth()->user();
                            $userInitial = $user ? strtoupper(substr($user->name ?? 'A', 0, 1)) : 'A';
                            $userName = $user ? ($user->name ?? 'Admin') : 'Admin';
                        @endphp
                        {{ $userInitial }}
                    </div>
                    <div>
                        <div style="font-size: 14px; font-weight: 600;">{{ $userName }}</div>
                        <div style="font-size: 12px; color: #666;">Quản trị viên</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            @yield('content')
        </div>
    </main>
    
    <div id="custom-toast-container" class="custom-toast-container"></div>

    <style>
        .custom-toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            z-index: 9999;
            cursor: pointer;
        }

        .custom-toast {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 16px;
            color: #fff;
            max-width: 320px;
            opacity: 0;
            transform: translateX(100%);
            transition: transform 0.4s ease, opacity 0.3s ease;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .custom-toast.show {
            opacity: 1;
            transform: translateX(0);
        }

        .custom-toast.success {
            background-color: #1c9a4a;
            /* xanh lá */
        }

        .custom-toast.error {
            background-color: #ef4444;
            /* đỏ */
        }

        .custom-toast.warning {
            background-color: #f59e0b;
            /* cam */
        }

        .custom-toast.info {
            background-color: #3b82f6;
            /* xanh dương */
        }

        .custom-toast-icon {
            font-size: 18px;
        }
    </style>

    <script>
        function showCustomToast(
            message = "Thông báo!",
            type = "info",
            duration = 5000
        ) {
            const container = document.getElementById("custom-toast-container");
            const toast = document.createElement("div");
            const icon = document.createElement("span");

            toast.className = `custom-toast ${type}`;
            icon.className = "custom-toast-icon";

            // Gán biểu tượng theo loại
            const icons = {
                success: "✅",
                error: "❌",
                warning: "⚠️",
                info: "💬",
            };
            icon.textContent = icons[type] || "🔔";

            toast.appendChild(icon);
            toast.appendChild(document.createTextNode(message));
            container.appendChild(toast);

            // Kích hoạt animation
            setTimeout(() => toast.classList.add("show"), 100);

            toast.addEventListener("click", () => {
                toast.classList.remove("show");
                setTimeout(() => {
                    container.removeChild(toast);
                }, 300);
                return;
            });

            // Gỡ thông báo sau duration
            setTimeout(() => {
                toast.classList.remove("show");
                setTimeout(() => {
                    container.removeChild(toast);
                }, 300);
                return;
            }, duration);
        }
        @php
            $alerts = [
                'success' => session('success'),
                'error'   => session('error'),
                'warning' => session('warning'),
                'info'    => session('info'),
            ];
        @endphp
        document.addEventListener("DOMContentLoaded", function() {
            let alerts = [];
            @foreach ($alerts as $type => $message)
                @if ($message)
                    alerts.push({type: '{{ $type }}', message: @json($message)});
                @endif
            @endforeach

            @if ($errors->any())
                @foreach ($errors->all() as $error)
                    alerts.push({type: 'error', message: @json($error)});
                @endforeach
            @endif

            @if(request()->cookie('updated_account_success'))
                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        showCustomToast(
                            "Cập nhật tài khoản thành công. Vui lòng đăng nhập lại.",
                            "success"
                        );
                    });
                </script>
            @endif

            alerts.forEach(a => showCustomToast(a.message, a.type));
        });
    </script>

    @php
        Cookie::queue(Cookie::forget('updated_account_success'));
    @endphp

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script>
        // Load and update notification count
        function loadNotificationCount() {
            fetch('{{ route("admin.notifications.unread-count") }}')
                .then(response => response.json())
                .then(data => {
                    const badge = document.getElementById('notificationBadge');
                    if (badge) {
                        if (data.count > 0) {
                            badge.textContent = data.count > 99 ? '99+' : data.count;
                            badge.style.display = 'flex';
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                })
                .catch(error => console.error('Error loading notification count:', error));
        }

        // Load notification count on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadNotificationCount();
            
            // Auto refresh every 30 seconds
            setInterval(loadNotificationCount, 30000);
        });

        // Load sidebar state from localStorage
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');
            const toggleIcon = document.getElementById('toggleIcon');
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            
            if (isCollapsed) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('sidebar-collapsed');
                toggleIcon.textContent = '☰';
            } else {
                sidebar.classList.remove('collapsed');
                mainContent.classList.remove('sidebar-collapsed');
                toggleIcon.textContent = '✕';
            }
            
            // Initialize mobile sidebar state
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('open');
            }
        });

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');
            const toggleIcon = document.getElementById('toggleIcon');
            const isMobile = window.innerWidth <= 768;
            
            if (isMobile) {
                // Mobile: toggle open class
                sidebar.classList.toggle('open');
            } else {
                // Desktop: toggle collapsed class
                const isCollapsed = sidebar.classList.contains('collapsed');
                
                if (isCollapsed) {
                    sidebar.classList.remove('collapsed');
                    mainContent.classList.remove('sidebar-collapsed');
                    toggleIcon.textContent = '✕';
                    localStorage.setItem('sidebarCollapsed', 'false');
                } else {
                    sidebar.classList.add('collapsed');
                    mainContent.classList.add('sidebar-collapsed');
                    toggleIcon.textContent = '☰';
                    localStorage.setItem('sidebarCollapsed', 'true');
                }
            }
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.querySelector('.menu-toggle');
            if (window.innerWidth <= 768 && sidebar.classList.contains('open')) {
                if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });

        // Menu accordion functionality
        document.addEventListener('DOMContentLoaded', function() {
            const menuGroups = document.querySelectorAll('.menu-group-header');
            
            menuGroups.forEach(header => {
                header.addEventListener('click', function(e) {
                    e.preventDefault();
                    const groupId = this.getAttribute('data-group');
                    const items = document.getElementById(groupId + '-group');
                    const isExpanded = items.classList.contains('expanded');
                    
                    // Close all other groups
                    document.querySelectorAll('.menu-group-items').forEach(item => {
                        item.classList.remove('expanded');
                    });
                    document.querySelectorAll('.menu-group-header').forEach(h => {
                        h.classList.remove('expanded');
                    });
                    
                    // Toggle current group
                    if (!isExpanded) {
                        items.classList.add('expanded');
                        this.classList.add('expanded');
                    }
                });
            });

            // Auto-expand groups with active items
            document.querySelectorAll('.menu-item.active').forEach(activeItem => {
                const group = activeItem.closest('.menu-group-items');
                if (group) {
                    group.classList.add('expanded');
                    const header = group.previousElementSibling;
                    if (header) {
                        header.classList.add('expanded');
                    }
                }
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script src="https://cdn.tiny.cloud/1/{{ env('APP_KEY_TINYMCE') }}/tinymce/8/tinymce.min.js" referrerpolicy="origin" crossorigin="anonymous"></script>

    {{-- Popup media picker dùng chung --}}
    @include('admins.media.modal-picker')

    @stack('scripts')

    <!-- Back to Top Button -->
    <button id="backToTop" class="back-to-top" aria-label="Lên đầu trang">
        <svg xmlns="http://www.w3.org/2000/svg" height="24" width="24" viewBox="0 0 640 640"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M270.8 111.8C266.3 122.5 264 134 264 145.6L264 246L112 360L112 312C112 298.7 101.3 288 88 288C74.7 288 64 298.7 64 312L64 456C64 469.3 74.7 480 88 480C101.3 480 112 469.3 112 456L112 448L264 448L264 502.4L198 555.2C194.2 558.2 192 562.8 192 567.7L192 592C192 600.8 199.2 608 208 608L296 608L296 568C296 554.7 306.7 544 320 544C333.3 544 344 554.7 344 568L344 608L432 608C440.8 608 448 600.8 448 592L448 567.7C448 562.8 445.8 558.2 442 555.2L376 502.4L376 448L528 448L528 456C528 469.3 538.7 480 552 480C565.3 480 576 469.3 576 456L576 312C576 298.7 565.3 288 552 288C538.7 288 528 298.7 528 312L528 360L376 246L376 145.6C376 134 373.7 122.5 369.2 111.8L342.1 46.8C338.4 37.8 329.7 32 320 32C310.3 32 301.6 37.8 297.8 46.8L270.7 111.8z"/></svg>
    </button>

    <style>
        .back-to-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            opacity: 0;
            visibility: hidden;
            transform: translateY(20px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 9999;
            padding: 0;
        }

        .back-to-top:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }

        .back-to-top:active {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .back-to-top.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .back-to-top svg {
            width: 24px;
            height: 24px;
            transition: transform 0.3s ease;
            fill: rgb(255, 255, 255);
        }

        .back-to-top:hover svg {
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .back-to-top {
                width: 45px;
                height: 45px;
                bottom: 15px;
                right: 15px;
            }

            .back-to-top svg {
                width: 20px;
                height: 20px;
            }
        }
    </style>

    <script>
        (function() {
            const backToTopButton = document.getElementById('backToTop');
            
            if (!backToTopButton) return;

            // Show/hide button based on scroll position
            function toggleBackToTop() {
                if (window.pageYOffset > 300) {
                    backToTopButton.classList.add('show');
                } else {
                    backToTopButton.classList.remove('show');
                }
            }

            // Scroll to top smoothly
            function scrollToTop() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }

            // Event listeners
            window.addEventListener('scroll', toggleBackToTop);
            backToTopButton.addEventListener('click', scrollToTop);

            // Initial check
            toggleBackToTop();
        })();
    </script>
</body>
</html>

