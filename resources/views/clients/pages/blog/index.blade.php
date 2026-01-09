@extends('clients.layouts.master')

@php
    $resolvedTitle = $pageTitle ?? ('Blog chia sẻ kiến thức kỹ thuật tự động hóa công nghiệp | ' . config('app.name'));
    $resolvedDescription = $pageDescription ?? 'Chia sẻ kiến thức kỹ thuật về cảm biến, PLC, HMI, biến tần, servo, encoder và giải pháp tự động hóa công nghiệp.';
    $resolvedKeywords = $pageKeywords ?? 'kiến thức kỹ thuật, blog tự động hóa, cảm biến công nghiệp, PLC HMI, biến tần';
    $canonicalUrl = route('client.blog.index');
    $pageImage = asset('clients/assets/img/business/' . ($settings->site_logo ?? 'no-image.webp'));
@endphp

@section('title', $resolvedTitle)

@push('js_page')
    <script defer src="{{ asset('clients/assets/js/main.js') }}"></script>
@endpush

@section('head')
    <meta name="description" content="{{ $resolvedDescription }}">
    <meta name="keywords" content="{{ $resolvedKeywords }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta property="og:title" content="{{ $resolvedTitle }}">
    <meta property="og:description" content="{{ $resolvedDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $settings->site_name ?? config('app.name') }}">
    <meta property="og:image" content="{{ $pageImage }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $resolvedTitle }}">
    <meta name="twitter:description" content="{{ $resolvedDescription }}">
    <meta name="twitter:image" content="{{ $pageImage }}">
    @if(!empty($shouldNoindex))
        <meta name="robots" content="noindex, follow">
    @endif
    <link rel="stylesheet" href="{{ asset('clients/assets/css/blog.css') }}">
@endsection

@section('schema')
    @if(isset($schemaData) && is_array($schemaData))
        @foreach($schemaData as $schema)
            <script type="application/ld+json">
                {!! json_encode($schema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) !!}
            </script>
        @endforeach
    @endif
@endsection

@section('content')
<section class="blog-page-container">
    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="blog-breadcrumb">
        <ol class="breadcrumb-list">
            <li class="breadcrumb-item">
                <a href="{{ route('client.home.index') }}">
                    <i class="fas fa-home"></i>
                    <span>Trang chủ</span>
                </a>
            </li>
            <li class="breadcrumb-separator">
                <i class="fas fa-chevron-right"></i>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <span>Tự động hóa</span>
            </li>
        </ol>
    </nav>

    <!-- HERO -->
    <div class="blog-hero">
        <div class="blog-hero-inner">
            <div class="blog-hero-content">
                @if($heroContextLabel)
                    <span class="hero-badge">{{ $heroContextLabel }}</span>
                @endif
                <h1 class="blog-hero-title">{{ $heroHeading ?? 'Góc kiến thức kỹ thuật tự động hóa' }}</h1>
                <p class="blog-hero-subtitle">
                    {{ $heroSubheading ?? ('Tổng hợp kiến thức kỹ thuật về cảm biến, PLC, HMI, biến tần và giải pháp tự động hóa từ ' . ($settings->site_name ?? config('app.name'))) }}
                </p>
                <ul class="hero-list">
                    <li>Đội ngũ kỹ sư cập nhật kiến thức mới nhất với kinh nghiệm thực tế.</li>
                    <li>Bám sát xu hướng công nghiệp 4.0, tối ưu hệ thống tự động hóa cho nhà máy.</li>
                    <li>Hướng dẫn chi tiết cho người mới bắt đầu lẫn kỹ sư có kinh nghiệm.</li>
                </ul>
            </div>

            <div class="blog-hero-stats">
                <div class="blog-hero-stat-item">
                    <div class="blog-hero-stat-number">{{ number_format($featuredPosts->count()) }}</div>
                    <span class="blog-hero-stat-label">Bài viết nổi bật</span>
                </div>
                <div class="blog-hero-stat-item">
                    <div class="blog-hero-stat-number">{{ number_format($posts->total()) }}</div>
                    <span class="blog-hero-stat-label">Kiến thức kỹ thuật</span>
                </div>
                <div class="blog-hero-stat-item">
                    <div class="blog-hero-stat-number">{{ number_format($sidebarCategories->count()) }}</div>
                    <span class="blog-hero-stat-label">Chủ đề chuyên ngành</span>
                </div>
            </div>
        </div>
    </div>

    <!-- FEATURED -->
    @if($featuredPosts->isNotEmpty())
        <section class="blog-featured">
            <div class="blog-featured-header">
                <h2 class="blog-featured-title">Bài viết nổi bật</h2>
                <span class="blog-featured-label">Được chọn bởi biên tập viên</span>
            </div>
            <div class="blog-featured-grid">
                @foreach($featuredPosts as $featured)
                    <article class="blog-featured-card">
                        @php
                            $featuredCover = $featured->coverImagePath();
                            $featuredCoverUrl = asset($featuredCover ?? 'clients/assets/img/posts/no-image.webp');
                        @endphp
                        <img src="{{ $featuredCoverUrl }}" alt="{{ $featured->title }}" loading="lazy">

                        <div class="blog-featured-card-body">
                            <span class="blog-featured-badge">
                                {{ $featured->category?->name ?? 'Tin tức' }}
                            </span>

                            <h3 class="blog-featured-card-title">
                                <a href="{{ route('client.blog.show', $featured) }}">
                                    {{ $featured->title }}
                                </a>
                            </h3>

                            <p class="blog-featured-card-excerpt">{{ $featured->excerpt ?? $settings->site_name ?? config('app.name') }}</p>

                            <div class="blog-featured-card-meta">
                                <span>{{ optional($featured->published_at)->format('d/m/Y') }}</span>
                                <span>{{ number_format($featured->views) }} xem</span>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <div class="blog-main-wrapper">
        <!-- MAIN POSTS -->
        <div class="blog-main-content">
            <div class="blog-posts-grid">
                @forelse($posts as $post)
                    <article class="blog-card">
                        @php
                            $coverPath = $post->coverImagePath();
                            $coverUrl = asset($coverPath ?? 'clients/assets/img/posts/no-image.webp');
                        @endphp
                        <img src="{{ $coverUrl }}" alt="{{ $post->title }}" loading="lazy">

                        <div class="blog-card-body">
                            <div class="blog-card-meta-top">
                                <span>{{ $post->category?->name ?? 'Tin tức' }}</span> •
                                <span>{{ optional($post->published_at)->format('d/m/Y') }}</span>
                            </div>

                            <h3 class="blog-card-title">
                                <a href="{{ route('client.blog.show', $post) }}">
                                    {{ $post->title }}
                                </a>
                            </h3>

                            <p class="blog-card-excerpt">{{ $post->excerpt ?? $post->meta_description ?? $settings->site_name ?? config('app.name') }}</p>

                            <div class="blog-card-footer">
                                <span>{{ number_format($post->views) }} xem</span>
                                <a href="{{ route('client.blog.show', $post) }}" class="blog-card-read-more">
                                    Đọc tiếp
                                </a>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="blog-empty">
                        <div class="blog-empty-message">
                            Đang cập nhật bài viết mới, bạn quay lại sau nhé!
                        </div>
                    </div>
                @endforelse
            </div>

            <div class="blog-pagination-wrapper">
                {{ $posts->links('clients.partials.pagination') }}
            </div>
        </div>

        <!-- SIDEBAR -->
        <aside class="blog-sidebar">

            <!-- Categories -->
            <div class="blog-sidebar-card">
                <h5 class="blog-sidebar-title">Chủ đề kỹ thuật</h5>
                <ul class="blog-sidebar-list">
                    @foreach($sidebarCategories as $category)
                        <li class="blog-sidebar-list-item">
                            <a href="{{ route('client.blog.index', ['category' => $category->slug]) }}">
                                {{ $category->name }}
                            </a>
                            <span class="blog-sidebar-count">{{ $category->posts_count }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <!-- Tags -->
            <div class="blog-sidebar-card">
                <h5 class="blog-sidebar-title">Từ khóa kỹ thuật</h5>
                <div class="blog-sidebar-tags">
                    @foreach($sidebarTags as $tag)
                        <a href="{{ route('client.blog.index', ['tag' => $tag->slug]) }}" class="blog-tag">#{{ $tag->name }}</a>
                    @endforeach
                </div>
            </div>

            <!-- Recent Posts -->
            <div class="blog-sidebar-card">
                <h5 class="blog-sidebar-title">Bài viết mới nhất</h5>
                <ul class="blog-sidebar-list">
                    @foreach($recentPosts as $recent)
                        <li class="blog-sidebar-post-item">
                            <a href="{{ route('client.blog.show', $recent) }}">
                                {{ $recent->title }}
                            </a>
                            <div class="blog-sidebar-post-date">{{ optional($recent->published_at)->format('d/m') }}</div>
                        </li>
                    @endforeach
                </ul>
            </div>

            <!-- Popular -->
            <div class="blog-sidebar-card">
                <h5 class="blog-sidebar-title">Được xem nhiều</h5>
                <ul class="blog-sidebar-list">
                    @foreach($popularPosts as $popular)
                        <li class="blog-sidebar-post-item">
                            <a href="{{ route('client.blog.show', $popular) }}">
                                {{ $popular->title }}
                            </a>
                            <div class="blog-sidebar-post-date">{{ number_format($popular->views) }} views</div>
                        </li>
                    @endforeach
                </ul>
            </div>

        </aside>
    </div>

</section>
@endsection
