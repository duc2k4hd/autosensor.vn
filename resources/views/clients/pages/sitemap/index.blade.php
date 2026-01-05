@extends('clients.layouts.master')

@section('title', $metaTitle ?? 'Sitemap - ' . config('app.name'))

@section('head')
    <meta name="description" content="{{ $metaDescription ?? 'Sitemap của ' . config('app.name') . ' - Tìm kiếm và khám phá tất cả các trang, sản phẩm, bài viết và danh mục trên website của chúng tôi.' }}">
    <meta name="keywords" content="{{ $metaKeywords ?? 'sitemap, ' . config('app.name') . ', bản đồ trang web, tìm kiếm nội dung' }}">
    <meta name="author" content="{{ $siteName ?? config('app.name') }}">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="googlebot" content="index, follow">
    <meta name="bingbot" content="index, follow">

    <link rel="canonical" href="{{ $sitemapUrl ?? route('client.sitemap.landing') }}">
    <link rel="alternate" hreflang="vi" href="{{ $sitemapUrl ?? route('client.sitemap.landing') }}">

    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $sitemapUrl ?? route('client.sitemap.landing') }}">
    <meta property="og:title" content="{{ $metaTitle ?? 'Sitemap - ' . config('app.name') }}">
    <meta property="og:description" content="{{ $metaDescription ?? 'Sitemap của ' . config('app.name') . ' - Tìm kiếm và khám phá tất cả các trang, sản phẩm, bài viết và danh mục trên website của chúng tôi.' }}">
    @php
        $ogImage = file_exists(public_path('clients/assets/img/og-sitemap.jpg'))
            ? asset('clients/assets/img/og-sitemap.jpg')
            : (file_exists(public_path('clients/assets/img/default-og.jpg'))
                ? asset('clients/assets/img/default-og.jpg')
                : asset('favicon.ico'));
    @endphp
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Sitemap - {{ $siteName ?? config('app.name') }}">
    <meta property="og:site_name" content="{{ $siteName ?? config('app.name') }}">
    <meta property="og:locale" content="vi_VN">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ $sitemapUrl ?? route('client.sitemap.landing') }}">
    <meta name="twitter:title" content="{{ $metaTitle ?? 'Sitemap - ' . config('app.name') }}">
    <meta name="twitter:description" content="{{ $metaDescription ?? 'Sitemap của ' . config('app.name') . ' - Tìm kiếm và khám phá tất cả các trang, sản phẩm, bài viết và danh mục trên website của chúng tôi.' }}">
    <meta name="twitter:image" content="{{ $ogImage }}">
    <meta name="twitter:image:alt" content="Sitemap - {{ $siteName ?? config('app.name') }}">

    <meta name="theme-color" content="#667eea">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <link rel="sitemap" type="application/xml" href="{{ $sitemapXmlUrl ?? url('/sitemap.xml') }}">
@endsection

@section('schema')
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebPage",
        "name": "{{ $metaTitle ?? 'Sitemap - ' . config('app.name') }}",
        "description": "{{ $metaDescription ?? 'Sitemap của ' . config('app.name') }}",
        "url": "{{ $sitemapUrl ?? route('client.sitemap.landing') }}",
        "inLanguage": "vi-VN",
        "isPartOf": {
            "@type": "WebSite",
            "name": "{{ $siteName ?? config('app.name') }}",
            "url": "{{ $siteUrl ?? config('app.url') }}"
        },
        "breadcrumb": {
            "@type": "BreadcrumbList",
            "itemListElement": [
                {
                    "@type": "ListItem",
                    "position": 1,
                    "name": "Trang chủ",
                    "item": "{{ $siteUrl ?? config('app.url') }}"
                },
                {
                    "@type": "ListItem",
                    "position": 2,
                    "name": "Sitemap",
                    "item": "{{ $sitemapUrl ?? route('client.sitemap.landing') }}"
                }
            ]
        },
        "mainEntity": {
            "@type": "ItemList",
            "name": "Sitemap Index",
            "description": "Danh sách tất cả các sitemap XML",
            "itemListElement": [
                @foreach($sitemaps as $index => $sitemap)
                {
                    "@type": "ListItem",
                    "position": {{ $index + 1 }},
                    "name": "{{ $sitemap['name'] }}",
                    "description": "{{ $sitemap['description'] }}",
                    "url": "{{ $sitemap['url'] }}"
                }@if(!$loop->last),@endif
                @endforeach
            ]
        }
    }
    </script>
@endsection

@push('styles')
    <style>
        /* Hero */
        .sitemap-hero {
            background: linear-gradient(135deg, #16a34a 0%, #22c55e 40%, #4ade80 100%);
            padding: 56px 0 40px;
            color: #f9fafb;
            text-align: left;
        }

        .sitemap-hero-content {
            max-width: 960px;
            margin: 0 auto;
            padding: 0 16px;
        }

        .sitemap-hero h1 {
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -0.03em;
            margin-bottom: 6px;
        }

        .sitemap-hero p {
            font-size: 15px;
            margin: 0;
            color: #d1fae5;
        }

        /* Main container */
        .sitemap-container {
            max-width: 1120px;
            margin: 0 auto;
            padding: 24px 16px 56px;
        }

        .sitemap-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .sitemap-card {
            background: #ffffff;
            border-radius: 14px;
            padding: 18px 18px 14px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.05);
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .sitemap-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 30px rgba(22, 163, 74, 0.2);
            border-color: #bbf7d0;
        }

        .sitemap-card-icon {
            font-size: 26px;
            margin-right: 8px;
        }

        .sitemap-card-title {
            font-size: 16px;
            font-weight: 700;
            color: #111827;
            margin: 0;
        }

        .sitemap-card-description {
            font-size: 13px;
            color: #6b7280;
            margin-top: 6px;
            margin-bottom: 10px;
        }

        .sitemap-card-url-wrapper {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .sitemap-card-url {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 600;
            color: #16a34a;
            text-decoration: none;
            padding: 6px 11px;
            border-radius: 999px;
            background: #ecfdf5;
            border: 1px solid #bbf7d0;
            width: fit-content;
        }

        .sitemap-card-url:hover {
            background: #16a34a;
            color: #f9fafb;
        }

        .sitemap-card-url-direct {
            display: block;
            font-size: 12px;
            color: #6b7280;
            background: #f9fafb;
            border-radius: 6px;
            padding: 4px 8px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            border: 1px solid #e5e7eb;
            word-break: break-all;
            text-decoration: none;
        }

        .sitemap-card-url-direct:hover {
            background: #e5e7eb;
            border-color: #cbd5e1;
        }

        .sitemap-info {
            border-radius: 12px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 18px 18px;
            font-size: 13px;
            color: #4b5563;
        }

        .sitemap-info h2 {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .sitemap-info ul {
            padding-left: 18px;
            margin: 8px 0 0;
        }

        .sitemap-info li {
            margin-bottom: 4px;
        }

        @media (max-width: 768px) {
            .sitemap-hero {
                padding: 40px 0 28px;
            }

            .sitemap-hero h1 {
                font-size: 24px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="sitemap-hero">
        <div class="sitemap-hero-content">
            <h1>🗺️ Sitemap</h1>
            <p>Khám phá toàn bộ nội dung trên website của chúng tôi một cách dễ dàng và nhanh chóng</p>
        </div>
    </div>

    <div class="sitemap-container">
        <div class="sitemap-grid">
            @foreach($sitemaps as $sitemap)
                <div class="sitemap-card">
                    <span class="sitemap-card-icon">{{ $sitemap['icon'] }}</span>
                    <h3 class="sitemap-card-title">{{ $sitemap['name'] }}</h3>
                    <p class="sitemap-card-description">{{ $sitemap['description'] }}</p>
                    <div class="sitemap-card-url-wrapper">
                        <a href="{{ $sitemap['url'] }}" target="_blank" class="sitemap-card-url">
                            📄 Xem sitemap XML
                        </a>
                        <a href="{{ $sitemap['url'] }}" target="_blank" class="sitemap-card-url-direct" title="Click để mở XML">
                            {{ $sitemap['url'] }}
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="sitemap-info">
            <h2>ℹ️ Thông tin về Sitemap</h2>
            <p>
                Sitemap giúp các công cụ tìm kiếm như Google, Bing dễ dàng thu thập và lập chỉ mục
                tất cả các trang trên website của chúng tôi. Điều này giúp cải thiện khả năng hiển thị
                của website trên các công cụ tìm kiếm.
            </p>
            <ul>
                <li>Sitemap được cập nhật tự động khi có nội dung mới</li>
                <li>Hỗ trợ đầy đủ các loại nội dung: bài viết, sản phẩm, danh mục, tags</li>
                <li>Tuân thủ chuẩn XML Sitemap Protocol của Google</li>
                <li>Tối ưu hóa cho SEO và công cụ tìm kiếm</li>
            </ul>
            <p style="margin-top: 24px; margin-bottom: 0;">
                <strong>Lưu ý:</strong> Các file sitemap XML được tạo tự động và có thể được submit
                trực tiếp lên Google Search Console để tăng tốc độ lập chỉ mục.
            </p>
        </div>
    </div>
@endsection

