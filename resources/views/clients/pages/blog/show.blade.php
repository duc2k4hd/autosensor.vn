@extends('clients.layouts.master')

@section(
    'title',
    (
        $pageTitle
        ?? $post->meta_title
        ?? $post->title
    ) . ' | ' . ($settings->site_name ?? 'AutoSensor Việt Nam')
)

@push('css_page')
    <link rel="stylesheet" href="{{ asset('clients/assets/css/blog.css') }}">
    <link
        rel="preload"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
        as="style"
        onload="this.onload=null;this.rel='stylesheet'">

    <noscript>
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    </noscript>
@endpush

@section('head')
    @php
        // Lấy ảnh LCP (ảnh đầu tiên trong gallery hoặc cover image)
        $lcpImage = null;
        $galleryImages = $post->images;
        if ($galleryImages->isNotEmpty()) {
            $firstImage = $galleryImages->first();
            $lcpImage = asset('clients/assets/img/posts/'.$firstImage->url);
        } else {
            $lcpImage = $coverAsset ?? asset('clients/assets/img/posts/no-image.webp');
        }
    @endphp
    
    {{-- Preload LCP Image - Tối ưu LCP --}}
    <link rel="preload" as="image" href="{{ $lcpImage }}" fetchpriority="high">
    
    {{-- Preconnect to image domain for faster loading --}}
    @php
        $parsedUrl = parse_url($lcpImage);
        if ($parsedUrl && isset($parsedUrl['scheme']) && isset($parsedUrl['host'])) {
            $imageDomain = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
        } else {
            $imageDomain = url('/');
        }
    @endphp
    <link rel="preconnect" href="{{ $imageDomain }}" crossorigin>
    
    {{-- SEO Meta Tags --}}
    <meta name="description" content="{{ $pageDescription ?? ($post->meta_description ?? $post->excerpt) }}">
    <meta name="keywords" content="{{ $pageKeywords ?? $post->meta_keywords }}">
    <link rel="canonical" href="{{ $canonicalUrl ?? route('client.blog.show', $post) }}">
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $pageTitle ?? ($post->meta_title ?? $post->title) }}">
    <meta property="og:description" content="{{ $pageDescription ?? ($post->meta_description ?? $post->excerpt) }}">
    <meta property="og:url" content="{{ $canonicalUrl ?? route('client.blog.show', $post) }}">
    <meta property="og:image" content="{{ $coverAsset ?? asset('clients/assets/img/posts/no-image.webp') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle ?? ($post->meta_title ?? $post->title) }}">
    <meta name="twitter:description" content="{{ $pageDescription ?? ($post->meta_description ?? $post->excerpt) }}">
    <meta name="twitter:image" content="{{ $coverAsset ?? asset('clients/assets/img/posts/no-image.webp') }}">
@endsection

@push('js_page')
    <script defer src="{{ asset('clients/assets/js/main.js') }}"></script>
@endpush

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
    @php
        $shareUrl = urlencode(route('client.blog.show', $post));
        $shareText = urlencode($post->title . ' - ' . config('app.name'));
        $galleryImages = $post->images;
        $firstImage = $galleryImages->isNotEmpty() ? asset('clients/assets/img/posts/'.$galleryImages->first()->url) : ($coverAsset ?? asset('clients/assets/img/posts/no-image.webp'));
    @endphp

    <!-- Social Icons Sidebar (Left) -->
    <div class="xanhworld_blog_social-icons">
        <a href="#" class="xanhworld_blog_social-icon xanhworld_blog_zalo" title="Zalo">
            <i class="fa fa-comment"></i>
        </a>
        <a href="#" class="xanhworld_blog_social-icon xanhworld_blog_messenger" title="Messenger">
            <i class="fab fa-facebook-messenger"></i>
        </a>
        <a href="#" class="xanhworld_blog_social-icon xanhworld_blog_chat" title="Chat">
            <i class="fa fa-comments"></i>
        </a>
        <a href="tel:19006026" class="xanhworld_blog_social-icon xanhworld_blog_phone" title="Gọi điện">
            <i class="fa fa-phone"></i>
        </a>
        <a href="#" class="xanhworld_blog_social-icon xanhworld_blog_location" title="Vị trí">
            <i class="fa fa-map-marker-alt"></i>
        </a>
        <a href="#" class="xanhworld_blog_social-icon xanhworld_blog_facebook" title="Facebook">
            <i class="fab fa-facebook-f"></i>
        </a>
    </div>

    <!-- Breadcrumb -->
    <div class="xanhworld_blog_breadcrumb">
        <i class="fa fa-home"></i>
        <a href="{{ route('client.home.index') }}">Trang chủ</a>
        <span>»</span>
        <a href="{{ route('client.blog.index') }}">Kiến thức kỹ thuật</a>
        @if($post->category)
            <span>»</span>
            <a href="{{ route('client.blog.index', ['category' => $post->category->slug]) }}">{{ $post->category->name }}</a>
        @endif
        <span>»</span>
        <span>{{ str()->limit($post->title, 60) }}</span>
    </div>

    <!-- Main Layout -->
    <div class="xanhworld_blog_main-wrapper">
        <!-- Left Sidebar - TOC (Replaced menu with TOC) -->
        <aside class="xanhworld_blog_left-sidebar" id="xanhworld_blog_leftSidebar">
            <div class="xanhworld-article-sidebar-content">
                @desktop
                    <!-- TOC - Replaced menu with TOC -->
                    @if($toc->isNotEmpty())
                        <div class="xanhworld-article-toc" id="toc-desktop">
                            <div class="xanhworld-article-toc-title">
                                <p>📑 Mục lục</p>
                            </div>
                            <ul class="xanhworld-article-toc-list">
                                @foreach($toc as $item)
                                    <li class="{{ $item['tag'] === 'h3' ? 'xanhworld-article-toc-item-h3' : '' }}">
                                        <a href="#{{ $item['id'] }}">{{ $item['label'] }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @enddesktop
            </div>
        </aside>

        <!-- Content Area -->
        <main class="xanhworld_blog_content-area">
            <!-- Featured Image Banner -->
            <div class="xanhworld_blog_featured-section">
                @if($galleryImages->isNotEmpty())
                    <div class="xanhworld-article-carousel" id="postImageCarousel">
                        <div class="xanhworld-article-carousel-inner">
                            @foreach($galleryImages as $index => $image)
                                @php
                                    $imgPath = 'clients/assets/img/posts/'.$image->url;
                                    $imgUrl = asset($imgPath);
                                    $isLcp = $index === 0;
                                @endphp
                                <div class="xanhworld-article-carousel-item {{ $isLcp ? 'active' : '' }}">
                                    <img src="{{ $imgUrl }}" alt="{{ $image->alt ?? $post->title }}" class="xanhworld_blog_featured-image"
                                         @if($isLcp)
                                         loading="eager"
                                         fetchpriority="high"
                                         @else
                                         loading="lazy"
                                         fetchpriority="auto"
                                         @endif>
                                </div>
                            @endforeach
                        </div>
                        @if($galleryImages->count() > 1)
                            <button class="xanhworld-article-carousel-prev" type="button" aria-label="Previous">
                                <span aria-hidden="true">‹</span>
                            </button>
                            <button class="xanhworld-article-carousel-next" type="button" aria-label="Next">
                                <span aria-hidden="true">›</span>
                            </button>
                        @endif
                    </div>
                @else
                    <img src="{{ $firstImage }}" alt="{{ $post->title }}" class="xanhworld_blog_featured-image" loading="eager" fetchpriority="high">
                @endif
                {{-- <div class="xanhworld_blog_featured-overlay">
                    <h2>{{ str()->limit($post->title, 50) }}<span>.</span></h2>
                    <p>{{ str()->limit($post->excerpt ?? $post->title, 100) }}</p>
                </div> --}}
            </div>

            <!-- Article Content -->
            <article class="xanhworld_blog_article">
                <div class="xanhworld_blog_article-header">
                    @if($post->category)
                        <span class="xanhworld_blog_article-category">{{ $post->category->name }}</span>
                    @endif
                    <h1 class="xanhworld_blog_article-title">{{ $post->title }}</h1>
                    <div class="xanhworld_blog_article-date">
                        <i class="fa fa-calendar"></i> {{ optional($post->published_at)->format('d/m/Y') ?? $post->updated_at->format('d/m/Y') }}
                    </div>
                    
                    @php
                        $colorAvatar = [
                            ['background' => '0D8ABC', 'color' => 'FFFFFF'],
                            ['background' => 'D32F2F', 'color' => 'FFFFFF'],
                            ['background' => 'E0E0E0', 'color' => '000000'],
                            ['background' => '388E3C', 'color' => 'FFFFFF'],
                            ['background' => 'F57C00', 'color' => 'FFFFFF'],
                            ['background' => '7B1FA2', 'color' => 'FFFFFF'],
                            ['background' => '00796B', 'color' => 'FFFFFF'],
                            ['background' => 'FFEB3B', 'color' => '000000'],
                        ];
                        $avatarColor = $colorAvatar[array_rand($colorAvatar)];
                    @endphp
                    <!-- Author Info -->
                    <div class="xanhworld-article-author-info" style="display: flex; align-items: center; gap: 12px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e5e5;">
                        <div class="xanhworld-article-author-avatar" style="width: 48px; height: 48px; border-radius: 50%; overflow: hidden; flex-shrink: 0;">
                            <img width="100%" height="100%" src="https://ui-avatars.com/api/?name={{ urlencode($post?->creator?->name ?? 'Đức Nobi 💖') }}&background={{ $avatarColor['background'] }}&color={{ $avatarColor['color'] }}&size=48&rounded=true" alt="">
                        </div>
                        <div class="xanhworld-article-author-details">
                            <h3 style="font-size: 16px; font-weight: 600; margin: 0; color: #333;">{{ $post?->creator?->name ?? 'Đội ngũ biên tập' }}</h3>
                            <p style="font-size: 13px; color: #999; margin: 4px 0 0 0;">📅 {{ optional($post->published_at)->format('d/m/Y') ?? $post->updated_at->format('d/m/Y') }}</p>
                        </div>
                    </div>
                </div>

                @mobile
                    <!-- TOC Mobile -->
                    @if($toc->isNotEmpty())
                        <div class="xanhworld-article-toc" id="toc-mobile" style="margin: 20px 30px; background: #f8f8f8; padding: 20px; border-radius: 8px;">
                            <div class="xanhworld-article-toc-title">
                                <p>📑 Mục lục</p>
                            </div>
                            <ul class="xanhworld-article-toc-list">
                                @foreach($toc as $item)
                                    <li class="{{ $item['tag'] === 'h3' ? 'xanhworld-article-toc-item-h3' : '' }}">
                                        <a href="#{{ $item['id'] }}">{{ $item['label'] }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endmobile

                <div class="xanhworld_blog_article-content">
                    {!! $contentWithAnchors !!}
                </div>

                <!-- Tags Section -->
                @if($tags->isNotEmpty())
                    <div class="xanhworld_blog_article-tags">
                        <div style="font-size: 14px; color: #666; margin-bottom: 12px;">
                            <strong style="color: #333;">Từ khóa:</strong>
                        </div>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                            @foreach($tags as $tag)
                                <a href="{{ route('client.blog.index', ['tags' => $tag->slug]) }}" style="display: inline-flex; align-items: center; padding: 4px 12px; border: 1px solid #ccc; border-radius: 16px; font-size: 12px; background: white; text-decoration: none; color: #333; transition: all 0.3s;">
                                    # {{ $tag->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Comments Section -->
                <div class="xanhworld-article-comments" style="padding: 0 30px 40px; border-top: 1px solid #e5e5e5; margin-top: 30px; padding-top: 30px;">
                    @include('clients.partials.comments', [
                        'type' => 'post',
                        'objectId' => $post->id,
                        'comments' => $comments ?? null,
                        'ratingStats' => $ratingStats ?? null,
                        'totalComments' => $totalComments ?? 0
                    ])
                </div>

                <!-- Bài viết liên quan -->
                @if($relatedPosts->isNotEmpty())
                    <div class="xanhworld_blog_related-posts">
                        <div class="xanhworld_blog_related-posts-title">
                            <h3>📰 Bài viết liên quan</h3>
                        </div>
                        <div class="xanhworld_blog_related-posts-grid">
                            @foreach($relatedPosts as $related)
                                @php
                                    $relatedPath = $related->coverImagePath();
                                    $relatedUrl = asset($relatedPath ?? 'clients/assets/img/posts/no-image.webp');
                                @endphp
                                <a href="{{ route('client.blog.show', $related) }}" class="xanhworld_blog_related-post-card">
                                    <div class="xanhworld_blog_related-post-image">
                                        <img src="{{ $relatedUrl }}" alt="{{ $related->title }}" loading="lazy">
                                    </div>
                                    <div class="xanhworld_blog_related-post-content">
                                        <h4 class="xanhworld_blog_related-post-title">{{ str()->limit($related->title, 70) }}</h4>
                                        <span class="xanhworld_blog_related-post-date">📅 {{ optional($related->published_at)->format('d/m/Y') ?? $related->created_at->format('d/m/Y') }}</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </article>
        </main>
    </div>


    <!-- Support Button -->
    <div class="xanhworld_blog_support-button" onclick="window.open('https://zalo.me/{{ $settings->contact_zalo ?? $settings->contact_phone ?? '' }}', '_blank')">
        <i class="fa fa-headset"></i>
        <span>Hỗ trợ trực tuyến</span>
    </div>
@endsection

@section('foot')
    <script>
        // Toggle Sidebar
        function toggleSidebar() {
            const sidebar = document.getElementById('xanhworld_blog_leftSidebar');
            if (sidebar) {
                sidebar.classList.toggle('xanhworld_blog_active');
            }
        }

        // Close sidebar when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('xanhworld_blog_leftSidebar');
            const toggle = document.querySelector('.xanhworld_blog_menu-toggle');
            
            if (sidebar && toggle) {
                if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                    sidebar.classList.remove('xanhworld_blog_active');
                }
            }
        });

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Add scroll animation to images
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        });

        document.querySelectorAll('.xanhworld_blog_article-content img').forEach(img => {
            img.style.opacity = '0';
            img.style.transform = 'translateY(20px)';
            img.style.transition = 'opacity 0.6s, transform 0.6s';
            observer.observe(img);
        });

        // Chat bubble animation
        const chatBubble = document.querySelector('.xanhworld_blog_chat-bubble');
        if (chatBubble) {
            setInterval(() => {
                chatBubble.style.animation = 'pulse 1s';
                setTimeout(() => {
                    chatBubble.style.animation = '';
                }, 1000);
            }, 5000);
        }

        // Add pulse animation
        if (!document.getElementById('blog-pulse-animation')) {
            const style = document.createElement('style');
            style.id = 'blog-pulse-animation';
            style.textContent = `
                @keyframes pulse {
                    0%, 100% { transform: scale(1); }
                    50% { transform: scale(1.1); }
                }
            `;
            document.head.appendChild(style);
        }

        // Carousel functionality - Giữ lại chức năng cũ
        document.addEventListener('DOMContentLoaded', () => {
            const carousel = document.getElementById('postImageCarousel');
            if (!carousel) return;

            const items = carousel.querySelectorAll('.xanhworld-article-carousel-item');
            const prevBtn = carousel.querySelector('.xanhworld-article-carousel-prev');
            const nextBtn = carousel.querySelector('.xanhworld-article-carousel-next');
            
            if (items.length <= 1) return;

            let currentIndex = 0;

            function showSlide(index) {
                items.forEach((item, i) => {
                    item.classList.remove('active');
                    if (i === index) {
                        item.classList.add('active');
                    }
                });
            }

            function nextSlide() {
                currentIndex = (currentIndex + 1) % items.length;
                showSlide(currentIndex);
            }

            function prevSlide() {
                currentIndex = (currentIndex - 1 + items.length) % items.length;
                showSlide(currentIndex);
            }

            if (prevBtn) {
                prevBtn.addEventListener('click', prevSlide);
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', nextSlide);
            }
        });

        // Auto Highlight TOC on scroll - Giữ lại chức năng cũ
        document.addEventListener('DOMContentLoaded', () => {
            const tocDesktop = document.getElementById('toc-desktop');
            const tocMobile = document.getElementById('toc-mobile');
            const tocContainer = tocDesktop || tocMobile;
            
            if (!tocContainer) return;

            const contentSections = document.querySelectorAll('.xanhworld_blog_article-content h2[id], .xanhworld_blog_article-content h3[id]');
            if (contentSections.length === 0) return;

            const tocLinks = new Map();
            tocContainer.querySelectorAll('a').forEach(link => {
                const href = link.getAttribute('href');
                if (href) {
                    const id = href.substring(1);
                    tocLinks.set(id, link);
                }
            });

            let activeId = null;
            const observer = new IntersectionObserver((entries) => {
                let currentEntry = null;
                for (const entry of entries) {
                    if (entry.isIntersecting && entry.intersectionRatio > 0) {
                        if (!currentEntry || entry.boundingClientRect.top < currentEntry.boundingClientRect.top) {
                            currentEntry = entry;
                        }
                    }
                }

                if (!currentEntry) {
                    const viewportTop = window.scrollY + 100;
                    for (const entry of entries) {
                        const rect = entry.boundingClientRect;
                        const elementTop = rect.top + window.scrollY;
                        if (elementTop <= viewportTop) {
                            if (!currentEntry || elementTop > (currentEntry.boundingClientRect.top + window.scrollY)) {
                                currentEntry = entry;
                            }
                        }
                    }
                }

                if (currentEntry) {
                    const id = currentEntry.target.getAttribute('id');
                    if (id && id !== activeId) {
                        activeId = id;
                        
                        tocContainer.querySelectorAll('a').forEach(link => {
                            link.classList.remove('active');
                        });
                        
                        const tocLink = tocLinks.get(id);
                        if (tocLink) {
                            tocLink.classList.add('active');
                            
                            if (tocContainer.scrollHeight > tocContainer.clientHeight) {
                                const linkTop = tocLink.offsetTop;
                                const linkHeight = tocLink.offsetHeight;
                                const containerHeight = tocContainer.clientHeight;
                                const scrollTop = tocContainer.scrollTop;
                                
                                if (linkTop < scrollTop) {
                                    tocContainer.scrollTop = linkTop - 20;
                                } else if (linkTop + linkHeight > scrollTop + containerHeight) {
                                    tocContainer.scrollTop = linkTop - containerHeight + linkHeight + 20;
                                }
                            }
                        }
                    }
                }
            }, {
                rootMargin: '-100px 0px -60% 0px',
                threshold: [0, 0.1, 0.5, 1]
            });

            contentSections.forEach((section) => {
                observer.observe(section);
            });

            const images = document.querySelectorAll('.xanhworld_blog_article-content img:not([loading])');
            images.forEach(img => {
                img.setAttribute('loading', 'lazy');
            });

            tocContainer.addEventListener('click', (e) => {
                const link = e.target.closest('a');
                if (!link || !link.hash) return;
                
                e.preventDefault();
                const targetId = link.hash.substring(1);
                const target = document.getElementById(targetId);
                if (target) {
                    const offset = 100;
                    const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - offset;
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                    
                    setTimeout(() => {
                        const id = target.getAttribute('id');
                        if (id) {
                            tocContainer.querySelectorAll('a').forEach(l => l.classList.remove('active'));
                            const tocLink = tocLinks.get(id);
                            if (tocLink) {
                                tocLink.classList.add('active');
                            }
                        }
                    }, 500);
                }
            });
        });
    </script>
@endsection
