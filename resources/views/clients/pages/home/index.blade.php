@extends('clients.layouts.master')

@section('title', 'AutoSensor Việt Nam - Cung cấp cảm biến, PLC, HMI, biến tần, servo, encoder, rơ le, nguồn công nghiệp' ?? ($settings->site_name ?? 'AutoSensor Việt Nam'))

@section('head')

    <link rel="stylesheet" href="{{ asset('clients/assets/css/home.css') }}">
    <link rel="stylesheet" href="{{ asset('clients/assets/css/shop-modal.css') }}">

    @if(optional($banners_home_parent->first())->image_desktop)
        <link rel="preload" as="image"
            href="{{ asset('clients/assets/img/banners/' . (optional($banners_home_parent->first())->image_desktop ?? 'banner.webp')) }}"
            onerror="this.onerror=null;this.src='{{ asset('clients/assets/img/banners/no-image.webp') }}';this.removeAttribute('srcset');this.removeAttribute('sizes');"
            fetchpriority="high">
    @endif

    <meta name="robots" content="index, follow, max-snippet:-1, max-video-preview:-1, max-image-preview:large" />

    <meta name="keywords"
        content="{{ $settings->seo_keywords ?? 'cảm biến công nghiệp, PLC, HMI, biến tần, servo, encoder, rơ le, nguồn công nghiệp, thiết bị tự động hóa, tự động hóa công nghiệp, thiết bị điều khiển' }}">

    <meta name="description"
        content="{{ $settings->site_description ?? 'Nhà cung cấp thiết bị tự động hóa công nghiệp hàng đầu Việt Nam. Chuyên cung cấp cảm biến, PLC, HMI, biến tần, servo, encoder, rơ le và giải pháp tự động hóa cho doanh nghiệp. Hỗ trợ kỹ thuật chuyên nghiệp, giao hàng nhanh.' }}">

    {{-- Open Graph --}}

    <meta property="og:title"
        content="{{ $settings->site_title ?? ($settings->subname ?? ($settings->site_name ?? 'AutoSensor Việt Nam')) }}">

    <meta property="og:description"
        content="{{ $settings->site_description ?? 'Nhà cung cấp thiết bị tự động hóa công nghiệp chuyên nghiệp. Cảm biến, PLC, HMI, biến tần, servo, encoder và giải pháp tự động hóa toàn diện cho doanh nghiệp.' }}">

    <meta property="og:url" content="{{ $settings->site_url ?? url('/') }}">

    <meta property="og:image"
        content="{{ asset('clients/assets/img/business/' . ($settings->site_banner ?? $settings->site_logo ?? 'no-image.webp')) }}">

    <meta property="og:image:width" content="1200">

    <meta property="og:image:height" content="630">

    <meta property="og:image:alt" content="{{ $settings->site_title ?? ($settings->site_name ?? 'AutoSensor Việt Nam') }}">

    <meta property="og:image:type" content="image/webp">

    <meta property="og:type" content="website">

    <meta property="og:site_name" content="{{ $settings->site_name ?? 'AutoSensor Việt Nam' }}">

    <meta property="og:locale" content="vi_VN">

    {{-- Twitter Card --}}

    <meta name="twitter:card" content="summary_large_image">

    <meta name="twitter:site" content="{{ $settings->site_name ?? 'AutoSensor Việt Nam' }}">

    <meta name="twitter:title"
        content="{{ $settings->site_title ?? ($settings->subname ?? ($settings->site_name ?? 'AutoSensor Việt Nam')) }}">

    <meta name="twitter:description"
        content="{{ $settings->site_description ?? 'Nhà cung cấp thiết bị tự động hóa công nghiệp hàng đầu. Cảm biến, PLC, HMI, biến tần, servo, encoder và giải pháp tự động hóa chuyên nghiệp.' }}">

    <meta name="twitter:image"
        content="{{ asset('clients/assets/img/business/' . ($settings->site_banner ?? $settings->site_logo ?? 'no-image.webp')) }}">

    <meta name="twitter:creator"
        content="{{ $settings->seo_author ?? ($settings->site_name ?? 'AutoSensor Việt Nam') }}">

    @php
        $homeUrl = $settings->site_url ?? url('/');
    @endphp
    <link rel="canonical" href="{{ $homeUrl }}">

    <link rel="alternate" hreflang="vi" href="{{ $homeUrl }}">

    <link rel="alternate" hreflang="x-default" href="{{ $homeUrl }}">

@endsection

@push('js_page')
    <script defer src="{{ asset('clients/assets/js/home.js') }}"></script>
@endpush

@section('schema')

    @include('clients.templates.schema_home')

@endsection

@section('content')

    <main @class(['autosensor_main'])>
        <!-- Hero: Left categories + Center slider + Right side banners -->
        <section @class(['autosensor_main_slider_main_hero'])>
            <!-- Left: Categories with hover submenus -->
            <aside @class(['autosensor_main_slider_main_cats'])>
                <h2 @class(['autosensor_main_slider_main_cats_title'])>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                        <!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.-->
                        <path
                            d="M96 160C96 124.7 124.7 96 160 96L480 96C515.3 96 544 124.7 544 160L544 480C544 515.3 515.3 544 480 544L160 544C124.7 544 96 515.3 96 480L96 160zM160 160L160 224L224 224L224 160L160 160zM480 160L288 160L288 224L480 224L480 160zM160 288L160 352L224 352L224 288L160 288zM480 288L288 288L288 352L480 352L480 288zM160 416L160 480L224 480L224 416L160 416zM480 416L288 416L288 480L480 480L480 416z" />
                    </svg>
                    Danh mục sản phẩm
                </h2>
                <ul @class(['autosensor_main_slider_main_cats_list'])>
                    @foreach($categories->take(8) as $cat)
                        <li @class(['autosensor_main_slider_main_cats_item'])>
                            <button onclick="window.location.href='/{{ $cat->slug ?? '' }}'" @class(['autosensor_main_slider_main_cats_btn'])>{{ $cat->name }}<span>›</span></button>
                            @if($cat->children && $cat->children->count())
                                <div @class(['autosensor_main_slider_main_cats_sub'])>
                                    <h3 @class(['autosensor_main_slider_main_cats_sub_title'])>{{ $cat->name }}</h3>
                                    @foreach($cat->children->take(9) as $child)
                                                <div @class(['autosensor_main_slider_main_cats_sub_item'])>
                                                    <a @class(['autosensor_main_slider_main_cats_sub_link']) href="/{{ $child->slug }}">{{
                                        $child->name }}</a>
                                                    @if($child->children && $child->children->count())
                                                        <div @class(['autosensor_main_slider_main_cats_sub2'])>
                                                            <h3 @class(['autosensor_main_slider_main_cats_sub_title'])>{{ $child->name }}</h3>
                                                            @foreach($child->children as $grand)
                                                                <a href="/{{ $grand->slug }}">{{ $grand->name }}</a>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                    @endforeach
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </aside>

            <!-- Center: Slider from database banners -->
            <div @class(['autosensor_main_slider_main_slider'])>
                <div @class(['autosensor_main_slider_main_slider_track'])>
                    @foreach($banners_home_parent as $i => $banner)
                        <div @class(['autosensor_main_slider_main_slide'])>
                            @if($banner->link)
                                <a href="{{ $banner->link }}" target="{{ $banner->target ?? '_blank' }}" rel="noopener">
                            @endif
                            <img onerror="this.src='{{ asset('clients/assets/img/banners/no-image.webp') }}'" {{ $i === 0 ? 'loading=eager fetchpriority=high' : 'loading=lazy' }}
                                src="{{ asset('clients/assets/img/banners/' . ($banner->image_desktop ?? 'no-image.webp')) }}"
                                onerror="this.onerror=null;this.src='{{ asset('clients/assets/img/banners/no-image.webp') }}';this.removeAttribute('srcset');this.removeAttribute('sizes');"
                                alt="{{ $banner->title ?? 'Banner' }}">
                            @if($banner->link)
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div @class(['autosensor_main_slider_main_nav'])>
                    <button type="button" class="autosensor_main_slider_prev">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                            <!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.-->
                            <path fill="#ffffff"
                                d="M41.4 342.6C28.9 330.1 28.9 309.8 41.4 297.3L169.4 169.3C178.6 160.1 192.3 157.4 204.3 162.4C216.3 167.4 224 179.1 224 192L224 256L560 256C586.5 256 608 277.5 608 304L608 336C608 362.5 586.5 384 560 384L224 384L224 448C224 460.9 216.2 472.6 204.2 477.6C192.2 482.6 178.5 479.8 169.3 470.7L41.3 342.7z" />
                        </svg>
                    </button>
                    <button type="button" class="autosensor_main_slider_next">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                            <!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.-->
                            <path fill="#ffffff"
                                d="M598.6 297.4C611.1 309.9 611.1 330.2 598.6 342.7L470.6 470.7C461.4 479.9 447.7 482.6 435.7 477.6C423.7 472.6 416 460.9 416 448L416 384L80 384C53.5 384 32 362.5 32 336L32 304C32 277.5 53.5 256 80 256L416 256L416 192C416 179.1 423.8 167.4 435.8 162.4C447.8 157.4 461.5 160.2 470.7 169.3L598.7 297.3z" />
                        </svg>
                    </button>
                </div>
                <div @class(['autosensor_main_slider_main_dots'])></div>
            </div>

            <!-- Right: side banners (2 banners bên phải) -->
            <aside @class(['autosensor_main_slider_main_side'])>
                @foreach($banners_home_children->take(2) as $banner)
                    @if($banner->link)
                        <a href="{{ $banner->link }}" target="{{ $banner->target ?? '_blank' }}" rel="noopener">
                    @endif
                    <img onerror="this.src='{{ asset('clients/assets/img/banners/no-image.webp') }}'" 
                         src="{{ asset('clients/assets/img/banners/' . ($banner->image_desktop ?? 'no-image.webp')) }}"
                         alt="{{ $banner->title ?? 'Banner' }}"
                         loading="lazy">
                    @if($banner->link)
                        </a>
                    @endif
                @endforeach
            </aside>
        </section>

        <hr>

        {{-- Flash Sale --}}
        @if ($flashSale && $flashSale->items && $flashSale->items->isNotEmpty())

            @php
                // Normalize flash sale end time → convert mọi kiểu về timestamp giây
                $normalizeTs = function ($v) {
                    if ($v instanceof \Illuminate\Support\Carbon) {
                        return $v->timezone(config('app.timezone', 'Asia/Ho_Chi_Minh'))->timestamp;
                    }

                    if (is_numeric($v)) {
                        $n = (int) $v;
                        return $n > 2147483647 ? (int) floor($n / 1000) : $n; // từ ms → s
                    }

                    return !empty($v)
                        ? \Illuminate\Support\Carbon::parse($v, config('app.timezone', 'Asia/Ho_Chi_Minh'))->timestamp
                        : null;
                };

                $homeFlashEndRaw = $flashSale->end_time
                    ?? ($flashSale->ends_at ?? ($flashSale->endAt ?? null));

                $homeFlashEnd = $normalizeTs($homeFlashEndRaw)
                    ?: now()->addHours(6)->timestamp;
            @endphp

            <script>
                const timeFlashSale = {{ $homeFlashEnd * 1000 }}; // milliseconds

            </script>

            <section class="autosensor_flash_sale">

                <div class="autosensor_flash_sale_header">

                    <h2 class="autosensor_flash_sale_title">FLASH SALE ⚡</h2>

                    <div class="autosensor_flash_sale_timer">
                        <span class="autosensor_flash_sale_timer_days">0</span><small>Ngày</small>
                        <span class="autosensor_flash_sale_timer_hours">0</span><small>Giờ</small>
                        <span class="autosensor_flash_sale_timer_minutes">0</span><small>Phút</small>
                        <span class="autosensor_flash_sale_timer_seconds">0</span><small>Giây</small>
                    </div>

                    <a href="{{ route('client.home.index') }}" class="autosensor_flash_sale_viewall">
                        Xem tất cả
                    </a>
                </div>

                <div class="autosensor_flash_sale_wrapper">

                    <div class="autosensor_flash_sale_list" id="flash-sale-scroll">

                        @foreach ($flashSale->items as $productSale)
                            @if (
                                    $productSale->is_active &&
                                    $productSale->stock > $productSale->sold &&
                                    $productSale->product &&
                                    $productSale->product->is_active
                                )
                                <div class="autosensor_flash_sale_item">

                                    <div class="autosensor_flash_sale_badge">
                                        {{ $productSale->product->primaryCategory->name ?? 'Sản phẩm' }}
                                    </div>

                                    <a href="/{{ $productSale->product->slug ?? '' }}">
                                        <img src="{{ asset('clients/assets/img/clothes/' . ($productSale->product->primaryImage->url ?? 'no-image.webp')) }}"
                                            alt="{{ $productSale->product->primaryImage->alt ?? $productSale->product->name ?? 'Thiết bị tự động hóa công nghiệp' }}"
                                            class="autosensor_flash_sale_img">
                                    </a>

                                    <div class="autosensor_flash_sale_info">

                                        <h3 class="autosensor_flash_sale_name">
                                            <a href="/{{ $productSale->product->slug ?? '' }}">
                                                {{ $productSale->product->name ?? 'Thiết bị tự động hóa công nghiệp' }}
                                            </a>
                                        </h3>

                                        @php
                                            $originalPrice = $productSale->original_price
                                                ?? $productSale->product->price
                                                ?? 0;

                                            $salePrice = $productSale->sale_price ?? 0;

                                            $discountPercent = $originalPrice > 0
                                                ? round((1 - $salePrice / $originalPrice) * 100)
                                                : 0;
                                        @endphp

                                        <div class="autosensor_flash_sale_discount">
                                            ⚡ -{{ $discountPercent }}%
                                        </div>

                                        <div class="autosensor_flash_sale_prices">
                                            <div class="autosensor_flash_sale_price sale">
                                                {{ number_format($salePrice, 0, ',', '.') }} đ
                                            </div>

                                            <div class="autosensor_flash_sale_price original">
                                                {{ number_format($originalPrice, 0, ',', '.') }} đ
                                            </div>
                                        </div>

                                        {{-- Trạng thái đã bán --}}
                                        <div class="autosensor_flash_sale_sold">
                                            @php
                                                $sold = $productSale->sold ?? 0;
                                                $stock = $productSale->stock ?? 0;

                                                $percentSold = $stock > 0 ? ($sold / $stock) * 100 : 0;
                                                $remaining = max(0, $stock - $sold);
                                            @endphp

                                            @if ($remaining <= 0) <span class="flash-sale-sold-out">⚠️ HẾT HÀNG</span>

                                            @elseif ($sold < 5) <span class="flash-sale-hot">🔥 ĐANG BÁN CHẠY</span>

                                            @elseif ($percentSold >= 90)
                                                <span class="flash-sale-low">⚠️ SẮP HẾT HÀNG</span>

                                            @else
                                                <span class="flash-sale-sold">Đã bán {{ $sold }}</span>
                                            @endif
                                        </div>

                                        {{-- Thanh tiến trình --}}
                                        <div class="autosensor_flash_sale_progress">
                                            <div class="autosensor_flash_sale_progress_fill"
                                                style="width: {{ $stock > 0 ? min(100, round(($sold / $stock) * 100)) : 0 }}%;">
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                    {{-- Nút điều hướng --}}
                    <div class="autosensor_flash_sale_nav autosensor_flash_sale_prev">&#10094;</div>
                    <div class="autosensor_flash_sale_nav autosensor_flash_sale_next">&#10095;</div>
                </div>
            </section>
        @endif


        <hr>

        <!-- Danh mục nổi bật -->

        <section @class(['autosensor_main_categories'])>
            <div @class(['autosensor_main_categories_title'])>
                <h2 @class(['autosensor_main_categories_title_name'])>Danh mục nổi bật</h2>
                <ul @class(['autosensor_main_categories_title_parent'])>
                    @foreach ($categories->take(7) as $category)
                        <li><a href="/{{ $category->slug ?? '' }}">{{ $category->name ?? 'Danh mục' }}</a>
                        </li>
                    @endforeach
                </ul>
                <div @class(['autosensor_main_categories_title_actions'])>
                    <div @class(['autosensor_main_categories_title_actions_prev'])>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                            <path
                                d="M48 256a208 208 0 1 1 416 0A208 208 0 1 1 48 256zm464 0A256 256 0 1 0 0 256a256 256 0 1 0 512 0zM217.4 376.9c4.2 4.5 10.1 7.1 16.3 7.1c12.3 0 22.3-10 22.3-22.3l0-57.7 96 0c17.7 0 32-14.3 32-32l0-32c0-17.7-14.3-32-32-32l-96 0 0-57.7c0-12.3-10-22.3-22.3-22.3c-6.2 0-12.1 2.6-16.3 7.1L117.5 242.2c-3.5 3.8-5.5 8.7-5.5 13.8s2 10.1 5.5 13.8l99.9 107.1z" />
                        </svg>
                    </div>
                    <div @class(['autosensor_main_categories_title_actions_next'])>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                            <path
                                d="M0 256a256 256 0 1 0 512 0A256 256 0 1 0 0 256zm395.3 11.3l-112 112c-4.6 4.6-11.5 5.9-17.4 3.5s-9.9-8.3-9.9-14.8l0-64-96 0c-17.7 0-32-14.3-32-32l0-32c0-17.7 14.3-32 32-32l96 0 0-64c0-6.5 3.9-12.3 9.9-14.8s12.9-1.1 17.4 3.5l112 112c6.2 6.2 6.2 16.4 0 22.6z" />
                        </svg>
                    </div>
                </div>
            </div>
            <div class="autosensor_main_categories_viewport">
                <div @class(['autosensor_main_categories_list'])>
                    @foreach ($categories as $category)
                        @foreach ($category->children as $child)
                            @php
                                // Sử dụng counts đã tính sẵn trong controller thay vì query trong loop
                                $productCount = $categoryProductCounts[$child->id] ?? 0;
                            @endphp
                            <div @class(['autosensor_main_categories_item'])> 
                                <a href="/{{ $child->slug }}">
                                    <img loading="lazy" draggable="false" @class(['autosensor_main_categories_item_img'])
                                        src="{{ asset('clients/assets/img/categories/' . ($child->image ?? 'no-image.webp')) }}"
                                        alt="{{ $child->name }}">
                                    <h3 @class(['autosensor_main_categories_item_title'])>{{ $child->name }}</h3>
                                    <p @class(['autosensor_main_categories_item_quantity'])>{{ $productCount }} sản phẩm</p>
                                </a>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </section>

        <hr>



        <!-- Ảnh khuyến mãi -->
        @if($vouchers && $vouchers->isNotEmpty())
            <section>
                <div @class(['autosensor_main_promotion'])>
                    @foreach ($vouchers as $voucher)
                        <div @class(['autosensor_main_promotion_item'])>
                            @php
                                // Xử lý ảnh voucher: có thể là URL, đường dẫn đầy đủ, hoặc chỉ tên file
                                $voucherImage = $voucher->image ?? null;
                                if ($voucherImage) {
                                    if (strpos($voucherImage, 'http') === 0) {
                                        // URL đầy đủ (CDN)
                                        $voucherImageUrl = $voucherImage;
                                    } elseif (strpos($voucherImage, 'clients/assets/img/vouchers') !== false) {
                                        // Đường dẫn đầy đủ (backward compatibility)
                                        $voucherImageUrl = asset($voucherImage);
                                    } else {
                                        // Chỉ là tên file
                                        $voucherImageUrl = asset('clients/assets/img/vouchers/'.$voucherImage);
                                    }
                                } else {
                                    $voucherImageUrl = asset('clients/assets/img/banners/banner.webp');
                                }
                            @endphp
                            <img draggable="false" loading="lazy" @class(['autosensor_main_promotion_item_img'])
                                src="{{ $voucherImageUrl }}"
                                alt="{{ $voucher->name ?? 'Khuyến mãi' }}">
                            <div @class(['autosensor_main_promotion_item_info'])>
                                <h4 @class(['autosensor_main_promotion_item_info_title'])>
                                    {{ $voucher->name ?? 'Khuyến mãi hấp dẫn' }}
                                </h4>
                                <p @class(['autosensor_main_promotion_item_info_desc'])>
                                    {{ $voucher->description ?? 'Ưu đãi giới hạn: freeship, giảm % và quà tặng cho đơn hàng thiết bị tự động hóa.' }}
                                </p>
                                <a href="{{ $voucher->link ?? route('client.home.index') }}"><button
                                        @class(['autosensor_main_promotion_item_info_btn'])>Khám phá ngay</button></a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
        <hr>

        <!-- Sản phẩm phổ biến -->

        <section>
            <div @class(['autosensor_main_popular_products'])>
                <div @class(['autosensor_main_popular_products_title'])>
                    <h2 @class(['autosensor_main_popular_products_title_name'])>Sản phẩm phổ biến</h2>
                    <div @class(['autosensor_main_popular_products_title_view_all'])>
                        <a @class(['autosensor_main_popular_products_title_view_all_active'])
                            href="{{ route('client.home.index') }}">Xem tất cả</a>
                        @foreach ($categories->take(7) as $category)
                            <a href="/{{ $category->slug ?? '' }}">{{ $category->name ?? 'Danh mục' }}</a>
                        @endforeach
                    </div>
                </div>
                <div @class(['autosensor_main_popular_products_list'])>
                    @if ($productsFeatured->count() > 0)
                        @foreach ($productsFeatured as $product)
                            <div @class(['autosensor_main_popular_products_item'])>
                                <div @class(['autosensor_main_popular_products_item_label'])>
                                    <span @class(['autosensor_main_popular_products_item_label_text'])>🔥 Đang thịnh
                                        hành</span>
                                </div>
                                <div @class(['autosensor_main_popular_products_item_img'])>
                                    <img draggable="false" loading="lazy"
                                        @class(['autosensor_main_popular_products_item_img_img'])
                                        src="{{ asset('clients/assets/img/clothes/' . ($product?->primaryImage?->url ?? 'no-image.webp')) }}"
                                        alt="{{ $product?->primary_image?->alt ?? $product?->name ?? 'Thiết bị tự động hóa công nghiệp' }}">
                                    <a @class(['autosensor_main_popular_products_item_img_khung'])
                                        href="/{{ $product?->slug ?? '' }}">
                                        <img draggable="false" loading="lazy"
                                            src="{{ asset('clients/assets/img/frame/' . ($product?->frame ?? 'frame-default.webp')) }}"
                                            alt="Khung ảnh sản phẩm" title="{{ $product?->name ?? 'Thiết bị tự động hóa công nghiệp' }}">
                                    </a>
                                </div>
                                <div @class(['autosensor_main_popular_products_item_info'])>
                                    <h4 @class(['autosensor_main_popular_products_item_info_category'])>
                                        {{ optional($product?->primaryCategory)->name ?? 'Danh mục sản phẩm' }}
                                    </h4>
                                    <a href="/{{ $product?->slug ?? '' }}">
                                        <h3 @class(['autosensor_main_popular_products_item_info_title'])>
                                            {{ $product?->name ?? 'TĂªn sản phẩm' }}
                                        </h3>
                                    </a>
                                    <div @class(['autosensor_main_popular_products_item_info_rating'])>
                                        <span @class(['autosensor_main_popular_products_item_info_rating_star'])>
                                            @php
                                                $star = $product?->display_rating_star ?? 5;
                                                for ($i = 1; $i <= $star; $i++) {
                                                    if ($star == 4) {
                                                        echo '<svg xmlns="http://www.w3.org/2000/svg" height="10" width="10" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path fill="#FFD43B" d="M341.5 45.1C337.4 37.1 329.1 32 320.1 32C311.1 32 302.8 37.1 298.7 45.1L225.1 189.3L65.2 214.7C56.3 216.1 48.9 222.4 46.1 231C43.3 239.6 45.6 249 51.9 255.4L166.3 369.9L141.1 529.8C139.7 538.7 143.4 547.7 150.7 553C158 558.3 167.6 559.1 175.7 555L320.1 481.6L464.4 555C472.4 559.1 482.1 558.3 489.4 553C496.7 547.7 500.4 538.8 499 529.8L473.7 369.9L588.1 255.4C594.5 249 596.7 239.6 593.9 231C591.1 222.4 583.8 216.1 574.8 214.7L415 189.3L341.5 45.1z"/></svg>';
                                                        if ($i == 4) {
                                                            echo '<svg xmlns="http://www.w3.org/2000/svg" height="10" width="10" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path fill="#FFD43B" d="M320.1 417.6C330.1 417.6 340 419.9 349.1 424.6L423.5 462.5L410.5 380C407.3 359.8 414 339.3 428.4 324.8L487.4 265.7L404.9 252.6C384.7 249.4 367.2 236.7 357.9 218.5L319.9 144.1L319.9 417.7zM489.4 553C482.1 558.3 472.4 559.1 464.4 555L320.1 481.6L175.8 555C167.8 559.1 158.1 558.3 150.8 553C143.5 547.7 139.8 538.8 141.2 529.8L166.4 369.9L52 255.4C45.6 249 43.4 239.6 46.2 231C49 222.4 56.3 216.1 65.3 214.7L225.2 189.3L298.8 45.1C302.9 37.1 311.2 32 320.2 32C329.2 32 337.5 37.1 341.6 45.1L415 189.3L574.9 214.7C583.8 216.1 591.2 222.4 594 231C596.8 239.6 594.5 249 588.2 255.4L473.7 369.9L499 529.8C500.4 538.7 496.7 547.7 489.4 553z"/></svg>';
                                                            break;
                                                        }
                                                    }
                                                    if ($star == 5) {
                                                        echo '<svg xmlns="http://www.w3.org/2000/svg" height="10" width="10" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path fill="#FFD43B" d="M341.5 45.1C337.4 37.1 329.1 32 320.1 32C311.1 32 302.8 37.1 298.7 45.1L225.1 189.3L65.2 214.7C56.3 216.1 48.9 222.4 46.1 231C43.3 239.6 45.6 249 51.9 255.4L166.3 369.9L141.1 529.8C139.7 538.7 143.4 547.7 150.7 553C158 558.3 167.6 559.1 175.7 555L320.1 481.6L464.4 555C472.4 559.1 482.1 558.3 489.4 553C496.7 547.7 500.4 538.8 499 529.8L473.7 369.9L588.1 255.4C594.5 249 596.7 239.6 593.9 231C591.1 222.4 583.8 216.1 574.8 214.7L415 189.3L341.5 45.1z"/></svg>';
                                                    }
                                            } @endphp </span>
                                        <span @class(['autosensor_main_popular_products_item_info_rating_count'])>
                                            <a href="/{{ $product?->slug ?? '' }}">({{ $product?->display_review_count ?? rand(10, 1000) }}
                                                đánh giá)</a>
                                        </span>
                                    </div>
                                    <div @class(['autosensor_main_popular_products_item_info_price'])>
                                        @if (!empty($product?->sale_price) && $product?->sale_price < $product?->price)
                                                        <span @class(['autosensor_main_popular_products_item_info_price_new'])>{{
                                            number_format($product?->sale_price ?? 0, 0, ',', '.') }} đ</span>
                                                        <span @class(['autosensor_main_popular_products_item_info_price_old'])>{{
                                            number_format($product?->price ?? $product?->sale_price, 0, ',', '.') }} đ</span>
                                        @else
                                            <span @class(['autosensor_main_popular_products_item_info_price_new'])>
                                                {{ number_format($product?->price ?? 0, 0, ',', '.') }} đ
                                            </span>
                                        @endif
                                    </div>
                                    @php
                                        $variantsData = method_exists($product, 'getVariantsData') ? $product->getVariantsData() : ($product->variants_data ?? []);
                                        $hasVariants = !empty($variantsData);
                                    @endphp
                                    @if($hasVariants)
                                        <div class="autosensor_main_popular_products_item_info_actions">
                                            {{-- Thêm vào giỏ --}}
                                            <button type="button" 
                                                    class="autosensor_main_popular_products_item_info_actions_add_to_cart open-variant-modal-btn" 
                                                    title="Thêm vào giỏ hàng"
                                                    data-product-id="{{ $product->id }}"
                                                    data-product-name="{{ $product->name }}"
                                                    data-product-slug="{{ $product->slug }}"
                                                    data-product-image="{{ asset('clients/assets/img/clothes/' . ($product?->primaryImage?->url ?? 'no-image.webp')) }}"
                                                    data-product-price="{{ $product->price }}"
                                                    data-product-sale-price="{{ $product->sale_price ?? '' }}"
                                                    data-variants='@json($variantsData)'>
                                                Thêm vào giỏ
                                            </button>

                                            {{-- Mua ngay --}}
                                            <button type="button" 
                                                    class="autosensor_main_popular_products_item_info_actions_wishlist open-variant-modal-btn" 
                                                    title="Đặt mua ngay"
                                                    data-product-id="{{ $product->id }}"
                                                    data-product-name="{{ $product->name }}"
                                                    data-product-slug="{{ $product->slug }}"
                                                    data-product-image="{{ asset('clients/assets/img/clothes/' . ($product?->primaryImage?->url ?? 'no-image.webp')) }}"
                                                    data-product-price="{{ $product->price }}"
                                                    data-product-sale-price="{{ $product->sale_price ?? '' }}"
                                                    data-variants='@json($variantsData)'
                                                    data-buy-now="true">
                                                Mua ngay
                                            </button>

                                                {{-- So sánh --}}
                                                <button type="button"
                                                    title="Thêm vào so sánh"
                                                    class="autosensor_main_popular_products_item_info_actions_compare add-to-comparison-btn"
                                                    data-product-id="{{ $product->id }}"
                                                    onclick="event.preventDefault(); event.stopPropagation(); return addProductToComparison({{ $product->id }});">So sánh</button>
                                        </div>
                                    @else
                                        <form action="{{ route('client.cart.store') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="product_id" value="{{ $product->id }}">

                                            <div class="autosensor_main_popular_products_item_info_actions">

                                                {{-- Thêm vào giỏ --}}
                                                <button type="submit" name="action" value="add_to_cart" title="Thêm vào giỏ hàng"
                                                    class="autosensor_main_popular_products_item_info_actions_add_to_cart">Thêm vào
                                                    giỏ</button>

                                                {{-- Mua ngay --}}
                                                <button type="submit" name="action" value="buy_now" title="Đặt mua ngay"
                                                    class="autosensor_main_popular_products_item_info_actions_wishlist">Mua ngay</button>

                                                {{-- So sánh --}}
                                                <button type="button"
                                                    title="Thêm vào so sánh"
                                                    class="autosensor_main_popular_products_item_info_actions_compare add-to-comparison-btn"
                                                    data-product-id="{{ $product->id }}"
                                                    onclick="event.preventDefault(); event.stopPropagation(); return addProductToComparison({{ $product->id }});">So sánh</button>
                                            </div>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
            <a @class(['autosensor_main_popular_product_show_all']) href="{{ route('client.home.index') }}">Xem tất cả</a>
        </section>



        <hr>

        {{-- Tabs Section với 5 danh mục --}}
        @if(isset($categoriesForTabs) && $categoriesForTabs->isNotEmpty())
            <div class="autosensor_home_tabs_wrapper">
                <div class="autosensor_home_tabs_header">
                    <h2 class="autosensor_home_tabs_title">Sản phẩm theo danh mục</h2>
                </div>
                <div class="autosensor_home_tabs_container">
                    @foreach($categoriesForTabs as $index => $category)
                        <button type="button" 
                                class="autosensor_home_tab_item {{ $index === 0 ? 'active' : '' }}" 
                                data-category-id="{{ $category->id }}"
                                data-tab-index="{{ $index }}">
                            {{ $category->name }}
                        </button>
                    @endforeach
                </div>
                <div class="autosensor_home_tabs_content">
                    @foreach($categoriesForTabs as $index => $category)
                        @php
                            $products = $categoryProducts[$category->id] ?? collect();
                        @endphp
                        <div class="autosensor_home_tab_panel {{ $index === 0 ? 'active' : '' }}" 
                             data-tab-panel="{{ $category->id }}">
                            <div class="autosensor_home_tab_products_grid">
                                @if($products->isNotEmpty())
                                    @foreach($products->take(10) as $product)
                                        @php
                                            $variantsData = method_exists($product, 'getVariantsData') ? $product->getVariantsData() : ($product->variants_data ?? []);
                                            $hasVariants = !empty($variantsData);
                                            $salePrice = $product->sale_price ?? $product->price ?? 0;
                                            $originalPrice = $product->price ?? 0;
                                            $hasDiscount = $salePrice < $originalPrice && $originalPrice > 0;
                                        @endphp
                                        <div class="autosensor_home_tab_product_card">
                                            <a href="/{{ $product->slug ?? '' }}" class="autosensor_home_tab_product_image_wrapper">
                                                <img loading="lazy" 
                                                    src="{{ asset('clients/assets/img/clothes/' . ($product?->primaryImage?->url ?? 'no-image.webp')) }}"
                                                    alt="{{ $product?->name ?? 'Sản phẩm' }}"
                                                    class="autosensor_home_tab_product_image">
                                                @if($hasDiscount)
                                                    <span class="autosensor_home_tab_product_discount">{{ round((($originalPrice - $salePrice) / $originalPrice) * 100) }}%</span>
                                                @endif
                                            </a>
                                            <div class="autosensor_home_tab_product_info">
                                                <h3 class="autosensor_home_tab_product_name">
                                                    <a href="/{{ $product->slug ?? '' }}">{{ $product->name ?? 'Sản phẩm' }}</a>
                                                </h3>
                                                <div class="autosensor_home_tab_product_price_wrapper">
                                                    @if($hasDiscount)
                                                        <span class="autosensor_home_tab_product_price_old">{{ number_format($originalPrice, 0, ',', '.') }}đ</span>
                                                    @endif
                                                    <span class="autosensor_home_tab_product_price_current">{{ number_format($salePrice, 0, ',', '.') }}đ</span>
                                                </div>
                                                <div class="autosensor_home_tab_product_action">
                                                    @if($hasVariants)
                                                        <button type="button" 
                                                            class="autosensor_home_tab_product_btn open-variant-modal-btn"
                                                            data-product-id="{{ $product->id }}"
                                                            data-product-name="{{ $product->name }}"
                                                            data-product-slug="{{ $product->slug }}"
                                                            data-product-image="{{ asset('clients/assets/img/clothes/' . ($product?->primaryImage?->url ?? 'no-image.webp')) }}"
                                                            data-product-price="{{ $product->price }}"
                                                            data-product-sale-price="{{ $product->sale_price ?? '' }}"
                                                            data-variants='@json($variantsData)'>
                                                            Mua ngay
                                                        </button>
                                                    @else
                                                        <form action="{{ route('client.cart.store') }}" method="POST" class="autosensor_home_tab_product_form">
                                                            @csrf
                                                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                                                            <button type="submit" class="autosensor_home_tab_product_btn">Mua ngay</button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="autosensor_home_tab_empty">Chưa có sản phẩm trong danh mục này.</div>
                                @endif
                            </div>
                            <a href="{{ route('client.shop.index', ['category' => $category->slug]) }}" class="autosensor_home_tab_viewmore">Xem thêm</a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <hr>

        <section class="autosensor_home_products_section">
            <h2 class="autosensor_home_products_title">Sản phẩm mới</h2>
            <div class="autosensor_home_products_container">
                <button type="button" class="autosensor_home_products_nav autosensor_home_products_nav_prev" aria-label="Trang trước">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" width="20" height="20" fill="currentColor">
                        <path d="M41.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L109.2 256 246.6 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-160 160z"/>
                    </svg>
                </button>
                <div class="autosensor_home_products_viewport">
                    <div class="autosensor_home_products_list">
                        @if ($productRandom->count() > 0)
                            @foreach ($productRandom as $product)
                                @php
                                    $variantsDataRandom = method_exists($product, 'getVariantsData') ? $product->getVariantsData() : ($product->variants_data ?? []);
                                    $hasVariantsRandom = !empty($variantsDataRandom);
                                    $salePrice = $product->sale_price ?? $product->price ?? 0;
                                    $originalPrice = $product->price ?? 0;
                                    $hasDiscount = $salePrice < $originalPrice && $originalPrice > 0;
                                    $discountPercent = $hasDiscount ? round((($originalPrice - $salePrice) / $originalPrice) * 100) : 0;
                                @endphp
                                <div class="autosensor_home_product_card">
                                    <a href="/{{ $product?->slug ?? '' }}" class="autosensor_home_product_image_wrapper">
                                        <img loading="lazy" 
                                            src="{{ asset('clients/assets/img/clothes/' . ($product?->primaryImage?->url ?? 'no-image.webp')) }}"
                                            alt="{{ $product?->primary_image?->alt ?? $product?->name ?? 'Sản phẩm' }}"
                                            class="autosensor_home_product_image">
                                        @if($hasDiscount && $discountPercent > 0)
                                            <span class="autosensor_home_product_discount">{{ $discountPercent }}%</span>
                                        @endif
                                    </a>
                                    <div class="autosensor_home_product_info">
                                        <h3 class="autosensor_home_product_title">
                                            <a href="/{{ $product?->slug ?? '' }}">{{ $product?->name ?? 'Sản phẩm' }}</a>
                                        </h3>
                                        <div class="autosensor_home_product_price_wrapper">
                                            @if($hasDiscount)
                                                <span class="autosensor_home_product_price_old">{{ number_format($originalPrice, 0, ',', '.') }}đ</span>
                                            @endif
                                            <span class="autosensor_home_product_price_current">{{ number_format($salePrice, 0, ',', '.') }}đ</span>
                                        </div>
                                        <div class="autosensor_home_product_action">
                                            @if($hasVariantsRandom)
                                                <button type="button" 
                                                    class="autosensor_home_product_btn open-variant-modal-btn"
                                                    data-product-id="{{ $product->id }}"
                                                    data-product-name="{{ $product->name }}"
                                                    data-product-slug="{{ $product->slug }}"
                                                    data-product-image="{{ asset('clients/assets/img/clothes/' . ($product?->primaryImage?->url ?? 'no-image.webp')) }}"
                                                    data-product-price="{{ $product->price }}"
                                                    data-product-sale-price="{{ $product->sale_price ?? '' }}"
                                                    data-variants='@json($variantsDataRandom)'>
                                                    Mua ngay
                                                </button>
                                            @else
                                                <form action="{{ route('client.cart.store') }}" method="POST" class="autosensor_home_product_form">
                                                    @csrf
                                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                                    <button type="submit" class="autosensor_home_product_btn">Mua ngay</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
                <button type="button" class="autosensor_home_products_nav autosensor_home_products_nav_next" aria-label="Trang sau">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" width="20" height="20" fill="currentColor">
                        <path d="M278.6 233.4c12.5 12.5 12.5 32.8 0 45.3l-160 160c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L210.7 256 73.4 118.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l160 160z"/>
                    </svg>
                </button>
            </div>
        </section>

        <hr>

        @include('clients.templates.call')

        <hr>

        <!-- TĂ­nh năng nổi bật -->

        <section>
            <div class="autosensor_main_features_highlight">
                <div class="autosensor_main_features_highlight_items">

                    <div class="autosensor_main_features_highlight_items_item">
                        <img draggable="false" loading="lazy"
                            src="{{ asset('clients/assets/img/other/giao-hang-free-re8243t34.png') }}"
                            alt="🚚 Miễn phí vận chuyển">

                        <h3 class="autosensor_main_features_highlight_items_item_title">
                            🚚 Miễn phí vận chuyển
                        </h3>

                        <p class="autosensor_main_features_highlight_items_item_desc">
                            Miễn phí vận chuyển cho đơn hàng từ 5.000.000đ tại khu vực nội thành.
                        </p>
                    </div>

                    <div class="autosensor_main_features_highlight_items_item">
                        <img draggable="false" loading="lazy"
                            src="{{ asset('clients/assets/img/other/ho-tro-24-7-398fhf384hf.jpg') }}"
                            alt="🤝 Hỗ trợ khách hàng 24/7">

                        <h3 class="autosensor_main_features_highlight_items_item_title">
                            🤝 Hỗ trợ khách hàng 24/7
                        </h3>

                        <p class="autosensor_main_features_highlight_items_item_desc">
                            Đội ngũ CSKH luôn sẵn sàng hỗ trợ bạn.
                        </p>
                    </div>

                    <div class="autosensor_main_features_highlight_items_item">
                        <img draggable="false" loading="lazy"
                            src="{{ asset('clients/assets/img/other/chinh_sach_doi_tra_hang-3489yfurhf34.jpg') }}"
                            alt="🔁 Chính sách đổi trả linh hoạt">

                        <h3 class="autosensor_main_features_highlight_items_item_title">
                            🔁 Chính sách đổi trả linh hoạt
                        </h3>

                        <p class="autosensor_main_features_highlight_items_item_desc">
                            Bảo hành chính hãng, đổi trả trong 30 ngày nếu lỗi kỹ thuật. Hỗ trợ nhanh chóng.
                        </p>
                    </div>

                    <div class="autosensor_main_features_highlight_items_item">
                        <img draggable="false" loading="lazy"
                            src="{{ asset('clients/assets/img/other/cam-ket-hang-chinh-hang-4387fy8734.png') }}"
                            alt="🏷️ Cam kết chính hãng">

                        <h3 class="autosensor_main_features_highlight_items_item_title">
                            🏷️ Cam kết chính hãng
                        </h3>

                        <p class="autosensor_main_features_highlight_items_item_desc">
                            Thiết bị chính hãng 100%, có chứng nhận CO/CQ, nguồn gốc xuất xứ rõ ràng.
                        </p>
                    </div>

                    <div class="autosensor_main_features_highlight_items_item">
                        <img draggable="false" loading="lazy"
                            src="{{ asset('clients/assets/img/other/hinh-thuc-thanh-toan-an-toan-348yy82y4rf.jpg') }}"
                            alt="💳 Thanh toán an toàn">

                        <h3 class="autosensor_main_features_highlight_items_item_title">
                            💳 Thanh toán an toàn
                        </h3>

                        <p class="autosensor_main_features_highlight_items_item_desc">
                            Nhiều phương thức thanh toán linh hoạt, bảo mật.
                        </p>
                    </div>

                </div>
            </div>
        </section>

        <!-- Đối tác của chúng tôi -->
        @if(isset($partners) && $partners->count() > 0)
            <section class="autosensor_partners_section autosensor_no_select">
                <div class="autosensor_partners_container">
                    <h2 class="autosensor_partners_title">Đối tác của chúng tôi</h2>
                    <div class="autosensor_partners_slider">
                        <div class="autosensor_partners_slider_track">
                            @foreach($partners as $partner)
                                <div class="autosensor_partners_slider_item">
                                    @php
                                        $imageUrl = null;
                                        if ($partner->image && file_exists(public_path($partner->image))) {
                                            $imageUrl = asset($partner->image);
                                        } else {
                                            $imageUrl = asset('clients/assets/img/business/no-image.webp');
                                        }
                                    @endphp
                                    <img src="{{ $imageUrl }}" 
                                        alt="{{ $partner->name }}" 
                                        class="autosensor_partners_slider_item_image"
                                        loading="lazy"
                                        decoding="async"
                                        onerror="this.onerror=null; this.src='{{ asset('clients/assets/img/business/no-image.webp') }}';">
                                </div>
                            @endforeach
                            {{-- Duplicate items for infinite scroll effect --}}
                            @foreach($partners as $partner)
                                <div class="autosensor_partners_slider_item">
                                    @php
                                        $imageUrl = null;
                                        if ($partner->image && file_exists(public_path($partner->image))) {
                                            $imageUrl = asset($partner->image);
                                        } else {
                                            $imageUrl = asset('clients/assets/img/business/no-image.webp');
                                        }
                                    @endphp
                                    <img src="{{ $imageUrl }}" 
                                        alt="{{ $partner->name }}" 
                                        class="autosensor_partners_slider_item_image"
                                        loading="lazy"
                                        decoding="async"
                                        onerror="this.onerror=null; this.src='{{ asset('clients/assets/img/business/no-image.webp') }}';">
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
        @endif
    </main>

    <!-- Modal chọn variant -->
    <div id="variant-modal" class="autosensor_variant_modal">
        <div class="autosensor_variant_modal_overlay"></div>
        <div class="autosensor_variant_modal_content">
            <button class="autosensor_variant_modal_close" aria-label="Đóng">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" width="20" height="20">
                    <path fill="currentColor" d="M324.5 411.1c6.2 6.2 16.4 6.2 22.6 0s6.2-16.4 0-22.6L214.6 256 347.1 123.5c6.2-6.2 6.2-16.4 0-22.6s-16.4-6.2-22.6 0L192 233.4 59.5 100.9c-6.2-6.2-16.4-6.2-22.6 0s-6.2 16.4 0 22.6L169.4 256 36.9 388.5c-6.2 6.2-6.2 16.4 0 22.6s16.4 6.2 22.6 0L192 278.6 324.5 411.1z"/>
                </svg>
            </button>
            <div class="autosensor_variant_modal_body">
                <div class="autosensor_variant_modal_product">
                    <div class="autosensor_variant_modal_product_image">
                        <img id="modal-product-image" src="" alt="">
                    </div>
                    <div class="autosensor_variant_modal_product_info">
                        <h3 id="modal-product-name" class="autosensor_variant_modal_product_name"></h3>
                        <div id="modal-product-price" class="autosensor_variant_modal_product_price"></div>
                    </div>
                </div>
                <div class="autosensor_variant_modal_variants">
                    <label class="autosensor_variant_modal_variants_label">Chọn biến thể:</label>
                    <div id="modal-variants-list" class="autosensor_variant_modal_variants_list"></div>
                </div>
                <div class="autosensor_variant_modal_quantity">
                    <label class="autosensor_variant_modal_quantity_label" for="modal-quantity">Số lượng:</label>
                    <div class="autosensor_variant_modal_quantity_controls">
                        <button type="button" class="autosensor_variant_modal_quantity_btn" data-action="decrease" aria-label="Giảm số lượng">-</button>
                        <input type="number" id="modal-quantity" value="1" min="1" class="autosensor_variant_modal_quantity_input" aria-label="Số lượng sản phẩm">
                        <button type="button" class="autosensor_variant_modal_quantity_btn" data-action="increase" aria-label="Tăng số lượng">+</button>
                    </div>
                </div>
                <div class="autosensor_variant_modal_actions">
                    <button type="button" class="autosensor_variant_modal_btn autosensor_variant_modal_btn_secondary" id="modal-cancel-btn">Hủy</button>
                    <button type="button" class="autosensor_variant_modal_btn autosensor_variant_modal_btn_primary" id="modal-add-to-cart-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" width="18" height="18" style="margin-right: 8px;">
                            <path fill="currentColor" d="M0 24C0 10.7 10.7 0 24 0L69.5 0c22 0 41.5 12.8 50.6 32l411 0c26.3 0 45.5 25 38.6 50.4l-41 152.3c-8.5 31.4-37 53.3-69.5 53.3l-288.5 0 5.4 28.5c2.2 11.3 12.1 19.5 23.6 19.5L488 336c13.3 0 24 10.7 24 24s-10.7 24-24 24l-288.3 0c-34.6 0-64.3-24.6-70.7-58.5L77.4 54.5c-.7-3.8-4-6.5-7.9-6.5L24 48C10.7 48 0 37.3 0 24zM128 464a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zm336-48a48 48 0 1 1 0 96 48 48 0 1 1 0-96z"/>
                        </svg>
                        Thêm vào giỏ hàng
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const tabsWrapper = document.querySelector('.autosensor_home_tabs_wrapper');
            if (!tabsWrapper) return;

            const tabItems = tabsWrapper.querySelectorAll('.autosensor_home_tab_item');
            const tabPanels = tabsWrapper.querySelectorAll('.autosensor_home_tab_panel');

            tabItems.forEach(function(tabItem) {
                tabItem.addEventListener('click', function() {
                    const categoryId = this.getAttribute('data-category-id');
                    
                    // Remove active class from all tabs
                    tabItems.forEach(function(item) {
                        item.classList.remove('active');
                    });
                    
                    // Add active class to clicked tab
                    this.classList.add('active');
                    
                    // Hide all panels
                    tabPanels.forEach(function(panel) {
                        panel.classList.remove('active');
                    });
                    
                    // Show corresponding panel
                    const targetPanel = tabsWrapper.querySelector(`[data-tab-panel="${categoryId}"]`);
                    if (targetPanel) {
                        targetPanel.classList.add('active');
                    }
                });
            });
        })();
    </script>

    @endsection

    @section('foot')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Xử lý modal chọn variant
            const modal = document.getElementById('variant-modal');
            if (!modal) {
                console.error('[Variant Modal] Modal element not found!');
                return;
            }

            const modalOverlay = modal.querySelector('.autosensor_variant_modal_overlay');
            const modalClose = modal.querySelector('.autosensor_variant_modal_close');
            const modalCancel = document.getElementById('modal-cancel-btn');
            const openModalBtns = document.querySelectorAll('.open-variant-modal-btn');
            const variantsList = document.getElementById('modal-variants-list');
            const quantityInput = document.getElementById('modal-quantity');
            const addToCartBtn = document.getElementById('modal-add-to-cart-btn');
            
            let currentProductId = null;
            let currentVariantId = null;
            let currentVariants = [];
            let maxStock = 999;
            let isBuyNow = false;

            // Hàm format currency
            function formatCurrencyVND(amount) {
                if (isNaN(amount)) return '0';
                return Number(amount).toLocaleString('vi-VN');
            }

            // Mở modal
            openModalBtns.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const productId = btn.dataset.productId;
                    const productName = btn.dataset.productName;
                    const productImage = btn.dataset.productImage;
                    const productPrice = parseFloat(btn.dataset.productPrice);
                    const productSalePrice = btn.dataset.productSalePrice ? parseFloat(btn.dataset.productSalePrice) : null;
                    isBuyNow = btn.dataset.buyNow === 'true';
                    let variants = [];
                    
                    try {
                        variants = JSON.parse(btn.dataset.variants || '[]');
                    } catch (e) {
                        console.error('Error parsing variants:', e);
                        variants = [];
                    }

                    currentProductId = productId;
                    currentVariants = variants;
                    
                    // Hiển thị thông tin sản phẩm
                    document.getElementById('modal-product-image').src = productImage;
                    document.getElementById('modal-product-image').alt = productName;
                    document.getElementById('modal-product-name').textContent = productName;
                    
                    // Hiển thị giá (lấy từ variant đầu tiên nếu có)
                    if (variants.length > 0) {
                        const firstVariant = variants[0];
                        updatePriceDisplay(firstVariant.display_price, firstVariant.price, firstVariant.sale_price);
                        currentVariantId = firstVariant.id;
                        maxStock = firstVariant.stock_quantity !== null ? firstVariant.stock_quantity : 999;
                        quantityInput.max = maxStock;
                    } else {
                        const displayPrice = productSalePrice && productSalePrice < productPrice ? productSalePrice : productPrice;
                        updatePriceDisplay(displayPrice, productPrice, productSalePrice);
                        currentVariantId = null;
                    }

                    // Render variants
                    renderVariants(variants);

                    // Reset quantity
                    quantityInput.value = 1;

                    // Update button text if buy now
                    if (isBuyNow) {
                        addToCartBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" width="18" height="18" style="margin-right: 8px;"><path fill="currentColor" d="M0 24C0 10.7 10.7 0 24 0L69.5 0c22 0 41.5 12.8 50.6 32l411 0c26.3 0 45.5 25 38.6 50.4l-41 152.3c-8.5 31.4-37 53.3-69.5 53.3l-288.5 0 5.4 28.5c2.2 11.3 12.1 19.5 23.6 19.5L488 336c13.3 0 24 10.7 24 24s-10.7 24-24 24l-288.3 0c-34.6 0-64.3-24.6-70.7-58.5L77.4 54.5c-.7-3.8-4-6.5-7.9-6.5L24 48C10.7 48 0 37.3 0 24zM128 464a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zm336-48a48 48 0 1 1 0 96 48 48 0 1 1 0-96z"/></svg>Mua ngay';
                    } else {
                        addToCartBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" width="18" height="18" style="margin-right: 8px;"><path fill="currentColor" d="M0 24C0 10.7 10.7 0 24 0L69.5 0c22 0 41.5 12.8 50.6 32l411 0c26.3 0 45.5 25 38.6 50.4l-41 152.3c-8.5 31.4-37 53.3-69.5 53.3l-288.5 0 5.4 28.5c2.2 11.3 12.1 19.5 23.6 19.5L488 336c13.3 0 24 10.7 24 24s-10.7 24-24 24l-288.3 0c-34.6 0-64.3-24.6-70.7-58.5L77.4 54.5c-.7-3.8-4-6.5-7.9-6.5L24 48C10.7 48 0 37.3 0 24zM128 464a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zm336-48a48 48 0 1 1 0 96 48 48 0 1 1 0-96z"/></svg>Thêm vào giỏ hàng';
                    }

                    // Hiển thị modal
                    modal.classList.add('active');
                    document.body.style.overflow = 'hidden';
                });
            });

            // Đóng modal
            function closeModal() {
                modal.classList.remove('active');
                document.body.style.overflow = '';
                currentProductId = null;
                currentVariantId = null;
                currentVariants = [];
                isBuyNow = false;
            }

            if (modalOverlay) modalOverlay.addEventListener('click', closeModal);
            if (modalClose) modalClose.addEventListener('click', closeModal);
            if (modalCancel) modalCancel.addEventListener('click', closeModal);

            // Render variants
            function renderVariants(variants) {
                if (!variantsList) return;
                
                variantsList.innerHTML = '';
                
                if (variants.length === 0) {
                    variantsList.innerHTML = '<p style="color: #999; padding: 20px; text-align: center;">Không có biến thể nào</p>';
                    return;
                }

                variants.forEach(function(variant, index) {
                    const variantBtn = document.createElement('button');
                    variantBtn.type = 'button';
                    variantBtn.className = 'autosensor_variant_modal_variant_item' + (index === 0 ? ' active' : '');
                    variantBtn.dataset.variantId = variant.id;
                    variantBtn.dataset.variantPrice = variant.display_price;
                    variantBtn.dataset.variantOriginalPrice = variant.price;
                    variantBtn.dataset.variantSalePrice = variant.sale_price || '';
                    variantBtn.dataset.variantStock = variant.stock_quantity !== null ? variant.stock_quantity : 'null';
                    
                    if (variant.stock_quantity !== null && variant.stock_quantity <= 0) {
                        variantBtn.classList.add('disabled');
                        variantBtn.disabled = true;
                    }

                    let variantHtml = '<span class="variant-name">' + (variant.name || '') + '</span>';
                    if (variant.details && variant.details.length > 0) {
                        variantHtml += '<span class="variant-details">(' + variant.details.join(', ') + ')</span>';
                    }
                    variantHtml += '<span class="variant-price">' + formatCurrencyVND(variant.display_price) + '₫</span>';
                    
                    if (variant.is_on_sale && variant.discount_percent) {
                        variantHtml += '<span class="variant-discount">-' + variant.discount_percent + '%</span>';
                    }
                    
                    if (variant.stock_quantity !== null && variant.stock_quantity <= 0) {
                        variantHtml += '<span class="variant-out-of-stock">Hết hàng</span>';
                    }

                    variantBtn.innerHTML = variantHtml;

                    variantBtn.addEventListener('click', function() {
                        if (this.classList.contains('disabled')) return;
                        
                        // Update active state
                        variantsList.querySelectorAll('.autosensor_variant_modal_variant_item').forEach(function(btn) {
                            btn.classList.remove('active');
                        });
                        this.classList.add('active');

                        // Update variant
                        currentVariantId = parseInt(this.dataset.variantId);
                        const variantPrice = parseFloat(this.dataset.variantPrice);
                        const variantOriginalPrice = parseFloat(this.dataset.variantOriginalPrice);
                        const variantSalePrice = this.dataset.variantSalePrice ? parseFloat(this.dataset.variantSalePrice) : null;
                        const variantStock = this.dataset.variantStock === 'null' ? null : parseInt(this.dataset.variantStock);

                        updatePriceDisplay(variantPrice, variantOriginalPrice, variantSalePrice);
                        
                        maxStock = variantStock !== null ? variantStock : 999;
                        quantityInput.max = maxStock;
                        
                        // Adjust quantity if exceeds max
                        if (parseInt(quantityInput.value) > maxStock) {
                            quantityInput.value = maxStock;
                        }
                    });

                    variantsList.appendChild(variantBtn);
                });
            }

            // Update price display
            function updatePriceDisplay(displayPrice, originalPrice, salePrice) {
                const priceContainer = document.getElementById('modal-product-price');
                if (!priceContainer) return;
                
                if (salePrice && salePrice < originalPrice) {
                    priceContainer.innerHTML = '<span class="price-new">' + formatCurrencyVND(displayPrice) + '₫</span><span class="price-old">' + formatCurrencyVND(originalPrice) + '₫</span>';
                } else {
                    priceContainer.innerHTML = '<span class="price-new">' + formatCurrencyVND(displayPrice) + '₫</span>';
                }
            }

            // Quantity controls
            document.querySelectorAll('.autosensor_variant_modal_quantity_btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    if (!quantityInput) return;
                    const action = this.dataset.action;
                    const currentValue = parseInt(quantityInput.value) || 1;
                    
                    if (action === 'increase') {
                        const newValue = Math.min(currentValue + 1, maxStock);
                        quantityInput.value = newValue;
                    } else if (action === 'decrease') {
                        const newValue = Math.max(currentValue - 1, 1);
                        quantityInput.value = newValue;
                    }
                });
            });

            if (quantityInput) {
                quantityInput.addEventListener('change', function() {
                    let value = parseInt(this.value) || 1;
                    value = Math.max(1, Math.min(value, maxStock));
                    this.value = value;
                });
            }

            // Add to cart
            if (addToCartBtn) {
                addToCartBtn.addEventListener('click', function() {
                    if (!currentProductId) return;
                    
                    const quantity = parseInt(quantityInput.value) || 1;
                    if (quantity < 1) {
                        alert('Số lượng phải lớn hơn 0');
                        return;
                    }

                    // Disable button
                    this.disabled = true;
                    const originalText = this.innerHTML;
                    this.innerHTML = '<span>Đang xử lý...</span>';

                    // Submit form
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route("client.cart.store") }}';
                    
                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = '{{ csrf_token() }}';
                    form.appendChild(csrfInput);

                    const productIdInput = document.createElement('input');
                    productIdInput.type = 'hidden';
                    productIdInput.name = 'product_id';
                    productIdInput.value = currentProductId;
                    form.appendChild(productIdInput);

                    if (currentVariantId) {
                        const variantIdInput = document.createElement('input');
                        variantIdInput.type = 'hidden';
                        variantIdInput.name = 'product_variant_id';
                        variantIdInput.value = currentVariantId;
                        form.appendChild(variantIdInput);
                    }

                    const quantityInputHidden = document.createElement('input');
                    quantityInputHidden.type = 'hidden';
                    quantityInputHidden.name = 'quantity';
                    quantityInputHidden.value = quantity;
                    form.appendChild(quantityInputHidden);

                    // If buy now, redirect to checkout
                    if (isBuyNow) {
                        const buyNowInput = document.createElement('input');
                        buyNowInput.type = 'hidden';
                        buyNowInput.name = 'action';
                        buyNowInput.value = 'buy_now';
                        form.appendChild(buyNowInput);
                    }

                    document.body.appendChild(form);
                    form.submit();
                });
            }

            // Close on Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modal.classList.contains('active')) {
                    closeModal();
                }
            });
        });
    </script>
@endsection