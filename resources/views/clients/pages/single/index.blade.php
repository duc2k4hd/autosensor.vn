@extends('clients.layouts.master')

@section('title', ($product->meta_title ?? $product->name) .' – AutoSensor Việt Nam' ?? ($product->name ? ($product->name. ' – AutoSensor Việt Nam') : 'AutoSensor Việt Nam - Chi tiết sản phẩm'))

@php
    $imgDesktop = asset('clients/assets/img/clothes/' . ($product?->primaryImage?->url ?? 'no-image.webp'));
    $imgMobile  = asset('clients/assets/img/clothes/resize/500x500/' . ($product?->primaryImage?->url ?? 'no-image.webp'));
@endphp

@push('css_page')
    <link rel="stylesheet" href="{{ asset('clients/assets/css/single.css?v='.time()) }}">
    <link rel="stylesheet" href="{{ asset('clients/assets/css/quick-consultation.css?v='.time()) }}">
    @if ($product?->primaryImage?->url)
        <link rel="preload"
            as="image"
            href="{{ $imgDesktop }}"
            fetchpriority="high">

        {{-- <link rel="preload"
            as="image"
            href="{{ $imgMobile }}"
            fetchpriority="high"> --}}
    @else
        <link rel="preload" as="image" href="{{ asset('clients/assets/img/clothes/no-image.webp') }}"
            fetchpriority="high">
    @endif
@endpush

@push('js_page')
    <script defer src="{{ asset('clients/assets/js/single.js?v='.time()) }}"></script>
    <script>
        // Dữ liệu sản phẩm cho popup tư vấn nhanh
        window.productData = {
            id: {{ $product->id }},
            name: @json($product->name),
            categoryIds: @json($product->category_ids ?? []),
        };
        
        // Debug: Log để kiểm tra
    </script>
    <script defer src="{{ asset('clients/assets/js/quick-consultation.js?v='.time()) }}"></script>
@endpush

@section('head')
    @php
        $siteUrl = rtrim($settings->site_url ?? 'https://autosensor.vn', '/');
        $productUrl = $siteUrl.'/'.($product->slug ?? '');
    @endphp

    <meta name="robots" content="index, follow, max-snippet:-1, max-video-preview:-1, max-image-preview:large"/>
    <meta name="keywords" content="{{ is_array($product->meta_keywords ?? null) ? implode(', ', $product->meta_keywords) : 'cảm biến công nghiệp, PLC, HMI, biến tần, servo, encoder, rơ le, thiết bị tự động hóa, AutoSensor Việt Nam' }}">

    <meta name="description"
        content="{{ $product->meta_description ?? ($product->meta_title ?? ($product->name ?? 'AutoSensor Việt Nam: Cảm biến, PLC, HMI, biến tần, servo, encoder và thiết bị tự động hóa. Giao hàng nhanh, bảo hành chính hãng, hỗ trợ kỹ thuật chuyên nghiệp.')) }}">

    <meta property="og:title"
        content="{{ $product->meta_title ?? ($product->name ?? 'AutoSensor Việt Nam - Thiết bị tự động hóa công nghiệp') }}">
    <meta property="og:description"
        content="{{ $product->meta_description ?? 'AutoSensor Việt Nam: Cảm biến, PLC, HMI, biến tần, servo, encoder và thiết bị tự động hóa. Hỗ trợ kỹ thuật chuyên nghiệp, giao hàng nhanh chóng.' }}">
    <meta property="og:url"
        content="{{ $productUrl }}">
    <meta property="og:image"
        content="{{ asset('clients/assets/img/clothes/' . ($product?->primaryImage?->url ?? 'no-image.webp')) }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt"
    content="{{ $product->meta_title ?? ($product->name ?? 'AutoSensor Việt Nam - Thiết bị tự động hóa công nghiệp') }}">
    <meta property="og:image:type" content="image/webp">
    <meta property="og:type" content="product">
    <meta property="og:site_name" content="{{ $settings->site_name ?? 'AutoSensor Việt Nam' }}">
    <meta property="og:locale" content="vi_VN">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="{{ $settings->site_name ?? 'AutoSensor Việt Nam' }}">
    <meta name="twitter:title"
        content="{{ $product->meta_title ?? ($product->name ?? 'AutoSensor Việt Nam - Thiết bị tự động hóa công nghiệp') }}">
    <meta name="twitter:description"
        content="{{ $product->meta_description ?? 'AutoSensor Việt Nam: Giao hàng nhanh, tư vấn kỹ thuật chuyên nghiệp, hỗ trợ lắp đặt và bảo hành thiết bị.' }}">
    <meta name="twitter:image"
    content="{{ asset('clients/assets/img/clothes/' . ($product?->primaryImage?->url ?? 'no-image.webp')) }}">
    <meta name="twitter:creator" content="{{ $settings->seo_author ?? 'AutoSensor Việt Nam' }}">

    <link rel="canonical" href="{{ $productUrl }}">
    <link rel="alternate" hreflang="vi" href="{{ $productUrl }}">
    <link rel="alternate" hreflang="x-default" href="{{ $productUrl }}">
@endsection

@section('schema')
    @include('clients.templates.schema_product')
@endsection


@section('content')
    @php
        $includedSets = collect($includedProducts ?? []);
    @endphp
    @include('clients.templates.admin_bar_product', ['product' => $product])
    <main class="autosensor_single">
        <!-- Breadcrumb -->
        <section>
            @php
                // Lấy danh mục cuối cùng của sản phẩm
                $categoryBreadcrumb = $product?->primaryCategory;

                // Truy ngược lên cha để tạo breadcrumb path
                $breadcrumbPath = collect();
                while ($categoryBreadcrumb) {
                    $breadcrumbPath->prepend($categoryBreadcrumb); // đưa vào đầu mảng
                    $categoryBreadcrumb = $categoryBreadcrumb->parent;
                }
            @endphp

            <div class="autosensor_single_breadcrumb">
                <a href="{{ url('/') }}">Trang chủ</a>
                <span class="separator">></span>

                @if ($breadcrumbPath->isNotEmpty())
                    @foreach ($breadcrumbPath as $breadcrumb)
                        <a href="/{{ $breadcrumb->slug }}">{{ $breadcrumb->name }}</a>
                        <span class="separator">></span>
                    @endforeach
                @endif

                <span class="breadcrumb-current">{{ $product->name }}</span>
            </div>
        </section>

        @php
            $listImg = [];
        @endphp
        
        <!-- Thông tin sản phẩm -->
        <section>
            <div class="autosensor_single_info">
                <div class="autosensor_single_info_images">
                    <div class="autosensor_single_info_images_main">
                        <img
                            src="{{ $imgDesktop }}"
                            srcset="
                                {{ $imgDesktop }} 500w,
                                {{ $imgDesktop }} 400w
                            "
                            sizes="(max-width: 768px) 500px, 400px"
                            width="400"
                            height="400"
                            loading="eager"
                            fetchpriority="high"
                            decoding="async"
                            alt="{{ ($product->name ?? 'Sản phẩm') . ' | ' . ($settings->site_name ?? 'AutoSensor Việt Nam') }}"
                            title="{{ ($product->name ?? 'Sản phẩm') . ' | ' . ($settings->site_name ?? 'AutoSensor Việt Nam') }}"
                            onerror="
                                if (!this.dataset.fallback) {
                                    this.dataset.fallback = 1;
                                    this.src='{{ asset('clients/assets/img/clothes/no-image.webp') }}';
                                }
                            "
                            class="autosensor_single_info_images_main_image"
                        >
                    </div>

                    @php
                        $variants = $product->variants ?? collect();
                        $hasVariants = $variants->isNotEmpty();
                        $firstVariant = $variants->first();
                        
                        // Map tồn kho đã dùng trong giỏ cho từng variant
                        $variantCartQuantities = collect($variantCartQuantities ?? []);
                        
                        // Nếu có variants, lấy giá và tồn kho (sau khi trừ trong giỏ) từ variant đầu tiên
                        if ($hasVariants && $firstVariant) {
                            $original = $firstVariant->price ?? 0;
                            $sale = $firstVariant->sale_price ?? null;
                            if ($sale && $sale > 0 && $sale < $original) {
                                // Có giá sale
                            } else {
                                $sale = null;
                            }
                            $inCartFirst = (int) $variantCartQuantities->get($firstVariant->id, 0);
                            $rawStockFirst = $firstVariant->stock_quantity;
                            $availableStock = $rawStockFirst !== null ? max(0, (int) $rawStockFirst - $inCartFirst) : null;
                            $isOutOfStock = $availableStock !== null && $availableStock <= 0;
                        } else {
                            // Không có variants, lấy từ product
                            $item = $product->isInFlashSale() ? $product->currentFlashSaleItem()->first() : $product;
                            $original = $item->original_price ?? ($item->price ?? 0);
                            $sale = $item->sale_price ?? 0;
                            $availableStock = max(0, (int) ($quantityProductDetail ?? 0));
                            $isOutOfStock = $availableStock <= 0;
                        }
                    @endphp

                    {{-- Tính % giảm --}}
                    @if($original > 0 && $sale && $sale > 0 && $sale < $original)
                        <span class="autosensor_single_info_specifications_sale">
                            -{{ round((($original - $sale) / $original) * 100) }}%
                        </span>
                    @endif
                    
                    @php
                        $overlayImages = ($product->images && $product->images->count() > 0)
                            ? $product->images
                            : ($product->primaryImage ? collect([$product->primaryImage]) : collect());
                    @endphp
                    
                    <div class="autosensor_single_info_images_gallery">
                        @if ($product->images && $product->images->count() > 0)
                            @php
                                // Xác định ảnh đang hiển thị (ảnh chính)
                                $primaryImageUrl = $product->primaryImage?->url ?? null;
                            @endphp
                            @foreach ($product->images as $index => $img)
                                @php
                                    // Chỉ set active cho ảnh đầu tiên (ảnh đang hiển thị trong main image)
                                    // Nếu có primaryImage, set active cho ảnh trùng với primaryImage
                                    // Nếu không có primaryImage, set active cho ảnh đầu tiên
                                    $shouldBeActive = false;
                                    if ($primaryImageUrl && $img->url === $primaryImageUrl) {
                                        // Ảnh trùng với primaryImage đang hiển thị
                                        $shouldBeActive = true;
                                    } elseif ($index === 0 && !$primaryImageUrl) {
                                        // Ảnh đầu tiên nếu không có primaryImage
                                        $shouldBeActive = true;
                                    } elseif ($index === 0 && $primaryImageUrl && !$product->images->firstWhere('url', $primaryImageUrl)) {
                                        // Ảnh đầu tiên nếu primaryImage không có trong danh sách images
                                        $shouldBeActive = true;
                                    }
                                @endphp
                                <img data-src="{{ asset('clients/assets/img/clothes/' . ($img->url ?? 'no-image.webp')) }}"
                                    onerror="this.onerror=null;this.src='{{ asset('clients/assets/img/clothes/no-image.webp') }}';this.removeAttribute('srcset');this.removeAttribute('sizes');"
                                    width="80" height="80"
                                    decoding="async"
                                    src="{{ asset('clients/assets/img/clothes/' . ($img->url ?? 'no-image.webp')) }}"
                            
                                    srcset="
                                        {{ asset('clients/assets/img/clothes/' . ($img->url ?? 'no-image.webp')) }} 85w
                                    "
                            
                                    sizes="(max-width: 1050px) 85px, 85px"
                            
                                    alt="{{ ($product->name ?? $img->alt). ' | '. ($settings->site_name ?? 'AutoSensor Việt Nam') ?? ($product->name ?? 'AutoSensor Việt Nam') }}"
                                    title="{{ ($product->name ?? $img->title). ' | '. ($settings->site_name ?? 'AutoSensor Việt Nam') ?? ($product->name ?? 'AutoSensor Việt Nam') }}"
                                    class="autosensor_single_info_images_gallery_image {{ $shouldBeActive ? 'autosensor_single_info_images_gallery_image_active' : '' }}">
                                @php
                                    $listImg[] = asset('clients/assets/img/clothes/' . ($img->url ?? 'no-image.webp'));
                                @endphp
                            @endforeach
                        @endif
                    </div>
                    
                    <div class="autosensor_single_info_images_support">
                        <form class="autosensor_single_info_images_support_form" id="phone-request-form" method="POST" action="{{ route('client.product.phone-request') }}">
                            @csrf
                            <div class="autosensor_single_info_images_support_form_group">
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                <input type="text" 
                                    placeholder="Nhập số điện thoại để được tư vấn kỹ thuật ({{ $settings->site_name ?? 'AutoSensor Việt Nam' }})."
                                    name="phone" 
                                    id="phone-input"
                                    class="autosensor_single_info_images_support_form_group_input"
                                    required
                                    pattern="[0-9]{10,11}"
                                    maxlength="11">
                                <button type="submit" class="autosensor_single_info_images_support_form_group_btn" id="phone-submit-btn">
                                    <span class="btn-text">Gửi yêu cầu</span>
                                    <span class="btn-loading" style="display: none;">Đang gửi...</span>
                                </button>
                            </div>
                            <div class="autosensor_single_info_images_support_form_notice">
                                <p class="autosensor_single_info_images_support_form_notice_text">Để lại số điện thoại,
                                    chúng tôi sẽ tư vấn cho bạn.</p>
                                <div id="phone-request-message" style="display: none; margin-top: 10px; padding: 8px; border-radius: 4px; font-size: 13px;"></div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="autosensor_single_info_specifications">
                    @if ($product->isInFlashSale())
                        @php
                            $flashSaleItem = $product->currentFlashSaleItem()->first() ?? $product;
                            $stock = (int) ($flashSaleItem->stock ?? 0);
                            $sold = (int) ($flashSaleItem->sold ?? 0);
                            $percentage = $stock > 0 ? min(100, round(($sold / $stock) * 100)) : 0;
                        @endphp
                        <script>
                            const endTime = new Date("{{ optional($product->currentFlashSale()->first())->end_time }}").getTime();
                        </script>
                        <div class="autosensor_single_info_specifications_deal">
                            <div class="autosensor_single_info_specifications_label">
                                ⚡ SĂN DEAL
                            </div>

                            <div class="autosensor_single_info_specifications_progress">
                                <div class="autosensor_single_info_specifications_progress_bar"
                                    style="width: {{ $percentage }}%;"></div>
                            </div>
                            <div class="autosensor_single_info_specifications_time">
                                <span class="autosensor_single_info_specifications_end_time">Kết thúc trong</span>
                                <div class="autosensor_single_info_specifications_countdown">
                                    <div
                                        class="autosensor_single_info_specifications_box autosensor_single_info_specifications_box_days">
                                        00</div>
                                    <span>:</span>
                                    <div
                                        class="autosensor_single_info_specifications_box autosensor_single_info_specifications_box_house">
                                        00</div>
                                    <span>:</span>
                                    <div
                                        class="autosensor_single_info_specifications_box autosensor_single_info_specifications_box_minute">
                                        00</div>
                                    <span>:</span>
                                    <div
                                        class="autosensor_single_info_specifications_box autosensor_single_info_specifications_box_second">
                                        00</div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="autosensor_single_info_specifications_title">
                        <h1 class="autosensor_single_info_specifications_title">{{ $product->name ?? 'Thiết bị tự động hóa công nghiệp chính hãng - AutoSensor Việt Nam' }}</h1>
                    </div>

                    <div class="autosensor_single_info_specifications_brand">
                        <!-- Thương hiệu + Mã sản phẩm -->
                        <div class="autosensor_single_info_specifications_brand_left">
                            <span>Mã sản phẩm:
                                <strong class="autosensor_single_info_specifications_brand_code">{{ $product->sku ?? 'AutoSensor' }}</strong>
                            </span>

                            @if($product->brand)
                                <span>Thương hiệu:
                                    <strong class="autosensor_single_info_specifications_brand_code">{{ $product?->brand?->name ?? 'AutoSensor Việt Nam' }}</strong>
                                </span>
                            @endif

                            <span>Bảo hành:
                                <strong class="autosensor_single_info_specifications_brand_code">12 tháng</strong>
                            </span>
                        </div>

                        <!-- Đánh giá -->
                        <div class="autosensor_single_info_specifications_brand_right">
                            <span class="autosensor_single_info_specifications_brand_stars">
                                @php
                                    $avg = $ratingStats['average_rating'] ?? 0;
                                    $hasReal = ($ratingStats['total_comments'] ?? 0) > 0 && $avg > 0;
                                    $star = $hasReal ? max(1, min(5, (int) round($avg))) : rand(4, 5);

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
                                    }
                                @endphp
                            </span>
                            @php
                                $realCount = $ratingStats['total_comments'] ?? 0;
                                $displayCount = $realCount > 0 ? $realCount : rand(10, 1000);
                            @endphp
                            <span onclick="tabReview()" class="autosensor_single_info_specifications_brand_reviews">
                                (<a href="#autosensor_review">{{ $displayCount }} đánh giá</a>)
                            </span>
                        </div>
                    </div>

                    {{-- Giá sản phẩm --}}
                    <p class="autosensor_single_info_specifications_price" id="product_price_display">
                        @if ($original > 0)
                            @if ($sale && $sale > 0 && $sale < $original)
                                {{-- Có giá khuyến mãi hợp lệ --}}
                                <meta content="VND">
                                <span class="autosensor_single_info_specifications_new_price">
                                    {{ number_format($sale, 0, ',', '.') }}₫
                                </span>

                                <meta content="2025-12-31" />
                                <span class="autosensor_single_info_specifications_old_price"
                                    style="text-decoration:line-through;">
                                    {{ number_format($original, 0, ',', '.') }}₫
                                </span>
                            @else
                                {{-- Không có sale, chỉ hiển thị giá gốc --}}
                                <meta content="2025-12-31" />
                                <span class="autosensor_single_info_specifications_new_price">
                                    {{ number_format($original, 0, ',', '.') }}₫
                                </span>
                                {{-- <span class="autosensor_single_info_specifications_sale">
                                    <svg style="width: 35px; height: 35px;" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 640 640">
                                        <path fill="#fff"
                                            d="M434.8 54.1C446.7 62.7 451.1 78.3 445.7 91.9L367.3 288L512 288C525.5 288 537.5 296.4 542.1 309.1C546.7 321.8 542.8 336 532.5 344.6L244.5 584.6C233.2 594 217.1 594.5 205.2 585.9C193.3 577.3 188.9 561.7 194.3 548.1L272.7 352L128 352C114.5 352 102.5 343.6 97.9 330.9C93.3 318.2 97.2 304 107.5 295.4L395.5 55.4C406.8 46 422.9 45.5 434.8 54.1z" />
                                    </svg>
                                </span> --}}
                            @endif
                        @endif
                        <a onclick="tabSizeGuide()" href="#autosensor_main_tab_guide" class="autosensor_main_size_guide">
                            Xem thông số
                        </a>
                    </p>

                    @if($hasVariants)
                        <!-- Variant Selector -->
                        <div class="autosensor_single_info_specifications_variants">
                            {{-- <label class="autosensor_single_info_specifications_variants_label">
                                Chọn biến thể:
                            </label> --}}
                            <div class="autosensor_single_info_specifications_variants_list">
                                @foreach($variants as $variant)
                                    @php
                                        $variantPrice = $variant->display_price;
                                        $variantSalePrice = $variant->sale_price;
                                        $variantStock = $variant->stock_quantity;
                                        $inCartVariant = (int) ($variantCartQuantities->get($variant->id, 0) ?? 0);
                                        $variantRemaining = $variantStock !== null ? max(0, (int) $variantStock - $inCartVariant) : null;
                                        $isOutOfStock = $variantRemaining !== null && $variantRemaining <= 0;
                                        
                                        // Lấy thông tin từ attributes
                                        $attrs = is_array($variant->attributes) ? $variant->attributes : (is_string($variant->attributes) ? json_decode($variant->attributes, true) : []);
                                        $size = $attrs['size'] ?? null;
                                        $hasPot = $attrs['has_pot'] ?? null;
                                        $comboType = $attrs['combo_type'] ?? null;
                                        $notes = $attrs['notes'] ?? null;
                                        
                                        // Xây dựng mô tả chi tiết
                                        $details = [];
                                        if ($size) $details[] = $size;
                                        if ($hasPot === true || $hasPot === '1' || $hasPot === 1) $details[] = 'Có phụ kiện đi kèm';
                                        if ($comboType) $details[] = $comboType;
                                        if ($notes) $details[] = $notes;
                                        $detailsText = !empty($details) ? ' ('.implode(', ', $details).')' : '';
                                    @endphp
                                    <button type="button" 
                                        class="autosensor_single_info_specifications_variant_item {{ $loop->first ? 'active' : '' }} {{ $isOutOfStock ? 'disabled' : '' }}"
                                        data-variant-id="{{ $variant->id }}"
                                        data-variant-price="{{ $variantPrice }}"
                                        data-variant-original-price="{{ $variant->price }}"
                                        data-variant-sale-price="{{ $variantSalePrice ?? 'null' }}"
                                        data-variant-stock="{{ $variantRemaining !== null ? $variantRemaining : 'null' }}"
                                        onclick="selectVariant({{ $variant->id }}, {{ $variant->price }}, {{ $variantSalePrice ? $variantSalePrice : 'null' }}, {{ $variantRemaining !== null ? $variantRemaining : 'null' }})"
                                        {{ $isOutOfStock ? 'disabled' : '' }}>
                                        <span class="variant-name">{{ $variant->sku ?? 'AutoSensor' }}</span>
                                        <div class="variant-price-row">
                                            <span class="variant-price">{{ number_format($variantPrice, 0, ',', '.') }}₫</span>
                                            @if($variant->isOnSale())
                                                <span class="variant-discount">-{{ $variant->discount_percent }}%</span>
                                            @endif
                                        </div>
                                        @if($variantRemaining !== null && $variantRemaining <= 0)
                                            <span class="variant-out-of-stock">Hết hàng</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                            <input type="hidden" name="product_variant_id" id="selected_variant_id" value="{{ $variants->first()?->id }}">
                        </div>
                    @endif

                    <!-- Product Actions Form -->
                    <form class="autosensor_single_info_specifications_actions" action="{{ route('client.cart.store') }}"
                        method="POST">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        @if($hasVariants)
                            <input type="hidden" name="product_variant_id" id="form_variant_id" value="{{ $variants->first()?->id }}">
                        @endif
                        <!-- Quantity Box -->
                        <div class="autosensor_single_info_specifications_actions_qty"
                            data-max-stock="{{ $hasVariants && $firstVariant ? ($firstVariant->stock_quantity ?? 9999) : max(1, $quantityProductDetail) }}"
                            id="quantity_box">
                            <button type="button" class="autosensor_single_info_specifications_actions_btn"
                                onclick="decreaseQty()">−</button>
                            <span class="autosensor_single_info_specifications_actions_value">1</span>
                            <button type="button" class="autosensor_single_info_specifications_actions_btn"
                                onclick="increaseQty()">+</button>
                        </div>
                        <input type="hidden" name="quantity" value="1" id="quantity_input">

                        <!-- Add to Cart -->
                        <button type="submit" name="action" value="add_to_cart"
                            class="autosensor_single_info_specifications_actions_cart {{ $isOutOfStock ? 'disabled' : '' }}"
                            {{ $isOutOfStock ? 'disabled' : '' }}>
                            THÊM VÀO GIỎ
                        </button>

                        <!-- Favorite button -->
                        <button type="button" @if(in_array($product->id, $favoriteProductIds ?? [])) onclick="removeWishlist({{ $product->id }})" @else onclick="addWishlist({{ $product->id }})" @endif class="autosensor_fav_btn {{ in_array($product->id, $favoriteProductIds ?? []) ? 'active autosensor_single_info_specifications_wishlish' : '' }}" aria-label="Yêu thích" style="">
                            @if(in_array($product->id, $favoriteProductIds ?? []))
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path fill="#ff0000" d="M305 151.1L320 171.8L335 151.1C360 116.5 400.2 96 442.9 96C516.4 96 576 155.6 576 229.1L576 231.7C576 343.9 436.1 474.2 363.1 529.9C350.7 539.3 335.5 544 320 544C304.5 544 289.2 539.4 276.9 529.9C203.9 474.2 64 343.9 64 231.7L64 229.1C64 155.6 123.6 96 197.1 96C239.8 96 280 116.5 305 151.1z"/></svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path fill="#ff0000" d="M442.9 144C415.6 144 389.9 157.1 373.9 179.2L339.5 226.8C335 233 327.8 236.7 320.1 236.7C312.4 236.7 305.2 233 300.7 226.8L266.3 179.2C250.3 157.1 224.6 144 197.3 144C150.3 144 112.2 182.1 112.2 229.1C112.2 279 144.2 327.5 180.3 371.4C221.4 421.4 271.7 465.4 306.2 491.7C309.4 494.1 314.1 495.9 320.2 495.9C326.3 495.9 331 494.1 334.2 491.7C368.7 465.4 419 421.3 460.1 371.4C496.3 327.5 528.2 279 528.2 229.1C528.2 182.1 490.1 144 443.1 144zM335 151.1C360 116.5 400.2 96 442.9 96C516.4 96 576 155.6 576 229.1C576 297.7 533.1 358 496.9 401.9C452.8 455.5 399.6 502 363.1 529.8C350.8 539.2 335.6 543.9 320 543.9C304.4 543.9 289.2 539.2 276.9 529.8C240.4 502 187.2 455.5 143.1 402C106.9 358.1 64 297.7 64 229.1C64 155.6 123.6 96 197.1 96C239.8 96 280 116.5 305 151.1L320 171.8L335 151.1z"/></svg>
                            @endif
                        </button>

                        <!-- Buy Now (same behavior as Add to Cart) -->
                        <a href="https://zalo.me/{{ $settings->contact_zalo ?? '0398951396' }}" class="autosensor_single_info_specifications_actions_buy {{ $isOutOfStock ? 'disabled' : '' }}"
                            {{ $isOutOfStock ? 'disabled' : '' }}>
                            Liên hệ mua hàng
                        </a>
                        
                    </form>

                    <p class="autosensor_single_info_specifications_stock">
                        @if ($isOutOfStock)
                            <span style="color: #d33;">Hết hàng</span>
                        @else
                            @if($hasVariants && $firstVariant)
                                @php
                                    $firstVariantInCart = (int) ($variantCartQuantities->get($firstVariant->id, 0) ?? 0);
                                    $firstRawStock = $firstVariant->stock_quantity;
                                    $firstRemaining = $firstRawStock !== null ? max(0, (int) $firstRawStock - $firstVariantInCart) : null;
                                @endphp
                                @if($firstRemaining !== null)
                                    Còn lại <strong class="autosensor_single_info_specifications_stock_value">{{ $firstRemaining }}</strong> sản phẩm
                                @else
                                    <span class="autosensor_single_info_specifications_stock_value">Còn hàng</span>
                                @endif
                            @else
                                Còn lại <strong class="autosensor_single_info_specifications_stock_value">{{ $quantityProductDetail ?? 0 }}</strong> sản phẩm
                            @endif
                        @endif
                    </p>

                    @if($includedSets->isNotEmpty())
                        <div class="autosensor_single_accessories_strip autosensor_no_select">
                            <div class="autosensor_single_accessories_strip_header">
                                <span class="autosensor_single_accessories_strip_title">🎯 Gợi ý phụ kiện đi kèm</span>
                            </div>
                            @foreach ($includedSets as $set)
                                @php
                                    $category = $set['category'] ?? null;
                                    $accessories = collect($set['products'] ?? []);
                                @endphp
                                @if($accessories->isNotEmpty())
                                    <div class="autosensor_single_accessories_group">
                                        <div class="autosensor_single_accessories_group_header">
                                            <h4 class="autosensor_single_accessories_group_title">
                                                {{ $category?->name ?? 'Danh mục khác' }}
                                            </h4>
                                            <div class="autosensor_single_accessories_group_nav">
                                                <button type="button" class="autosensor_single_accessories_nav_btn autosensor_single_accessories_nav_prev" data-group-index="{{ $loop->index }}" aria-label="Trước">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M15 18l-6-6 6-6"/>
                                                    </svg>
                                                </button>
                                                <button type="button" class="autosensor_single_accessories_nav_btn autosensor_single_accessories_nav_next" data-group-index="{{ $loop->index }}" aria-label="Sau">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M9 18l6-6-6-6"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="autosensor_single_accessories_wrapper">
                                            <div class="autosensor_single_accessories_scroller" data-accessory-scroll data-group-index="{{ $loop->index }}">
                                                @foreach ($accessories as $accessory)
                                                    @php
                                                        $accessoryVariants = $accessory->variants ?? collect();
                                                        $hasAccessoryVariants = $accessoryVariants->isNotEmpty();
                                                        
                                                        // Chuẩn bị dữ liệu variants cho JavaScript
                                                        $accessoryVariantsData = [];
                                                        if ($hasAccessoryVariants) {
                                                            foreach ($accessoryVariants as $variant) {
                                                                $attrs = is_array($variant->attributes) ? $variant->attributes : (is_string($variant->attributes) ? json_decode($variant->attributes, true) : []);
                                                                $accessoryVariantsData[] = [
                                                                    'id' => $variant->id,
                                                                    'name' => $variant->name,
                                                                    'price' => $variant->price,
                                                                    'sale_price' => $variant->sale_price,
                                                                    'display_price' => $variant->display_price,
                                                                    'stock_quantity' => $variant->stock_quantity,
                                                                    'attributes' => $attrs,
                                                                ];
                                                            }
                                                        }
                                                    @endphp
                                                    <div class="autosensor_single_accessories_item">
                                                        <a href="{{ route('client.product.detail', ['slug' => $accessory->slug ?? '']) }}" class="autosensor_single_accessories_item_thumb">
                                                            <img loading="lazy" decoding="async" 
                                                                src="{{ asset('clients/assets/img/clothes/' . ($accessory?->primaryImage?->url ?? 'no-image.webp')) }}"
                                                                alt="{{ $accessory->name ?? '' }}"
                                                                onerror="this.onerror=null;this.src='{{ asset('clients/assets/img/clothes/no-image.webp') }}';this.removeAttribute('srcset');this.removeAttribute('sizes');">
                                                        </a>
                                                        <div class="autosensor_single_accessories_item_name">{{ $accessory->name }}</div>
                                                        <div class="autosensor_single_accessories_item_price">
                                                            {{ number_format($accessory->sale_price ?? $accessory->price ?? 0, 0, ',', '.') }}đ
                                                        </div>
                                                        <button type="button"
                                                            class="autosensor_single_accessories_item_btn"
                                                            data-accessory-add="{{ $accessory->id }}"
                                                            data-accessory-name="{{ $accessory->name }}"
                                                            data-accessory-image="{{ asset('clients/assets/img/clothes/' . ($accessory?->primaryImage?->url ?? 'no-image.webp')) }}"
                                                            data-accessory-price="{{ $accessory->price ?? 0 }}"
                                                            data-accessory-sale-price="{{ $accessory->sale_price ?? '' }}"
                                                            data-accessory-has-variants="{{ $hasAccessoryVariants ? '1' : '0' }}"
                                                            @if($hasAccessoryVariants)
                                                                data-accessory-variants='@json($accessoryVariantsData)'
                                                            @endif>
                                                            + Thêm nhanh
                                                        </button>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>

                    <script>
                    (function() {
                        // Khởi tạo carousel cho mỗi group
                        document.querySelectorAll('[data-accessory-scroll]').forEach((carousel) => {
                            const groupIndex = carousel.getAttribute('data-group-index');
                            const prevBtn = document.querySelector(`.autosensor_single_accessories_nav_prev[data-group-index="${groupIndex}"]`);
                            const nextBtn = document.querySelector(`.autosensor_single_accessories_nav_next[data-group-index="${groupIndex}"]`);
                            const items = carousel.querySelectorAll('.autosensor_single_accessories_item');
                            
                            if (items.length === 0) return;

                            let isDragging = false;
                            let startX = 0;
                            let scrollLeft = 0;

                            // Tính số item hiển thị
                            function getVisibleItems() {
                                const width = carousel.offsetWidth;
                                if (width >= 1200) return 6;
                                if (width >= 992) return 5;
                                if (width >= 768) return 4;
                                if (width >= 576) return 3;
                                return 2;
                            }

                            // Scroll đến vị trí
                            function scrollTo(direction) {
                                const visibleItems = getVisibleItems();
                                const itemWidth = items[0].offsetWidth + 12; // width + gap
                                const scrollAmount = itemWidth * visibleItems;
                                
                                carousel.scrollBy({
                                    left: direction === 'next' ? scrollAmount : -scrollAmount,
                                    behavior: 'smooth'
                                });
                            }

                            // Cập nhật nút
                            function updateButtons() {
                                const isAtStart = carousel.scrollLeft <= 0;
                                const isAtEnd = carousel.scrollLeft >= carousel.scrollWidth - carousel.offsetWidth - 10;
                                
                                if (prevBtn) {
                                    prevBtn.disabled = isAtStart;
                                    prevBtn.classList.toggle('disabled', isAtStart);
                                }
                                if (nextBtn) {
                                    nextBtn.disabled = isAtEnd;
                                    nextBtn.classList.toggle('disabled', isAtEnd);
                                }
                            }

                            // Nút điều hướng
                            if (prevBtn) {
                                prevBtn.addEventListener('click', () => scrollTo('prev'));
                            }
                            if (nextBtn) {
                                nextBtn.addEventListener('click', () => scrollTo('next'));
                            }

                            // Swipe/Drag
                            carousel.addEventListener('mousedown', (e) => {
                                isDragging = true;
                                startX = e.pageX - carousel.offsetLeft;
                                scrollLeft = carousel.scrollLeft;
                                carousel.style.cursor = 'grabbing';
                                carousel.style.userSelect = 'none';
                            });

                            carousel.addEventListener('touchstart', (e) => {
                                isDragging = true;
                                startX = e.touches[0].pageX - carousel.offsetLeft;
                                scrollLeft = carousel.scrollLeft;
                            });

                            carousel.addEventListener('mouseleave', () => {
                                isDragging = false;
                                carousel.style.cursor = 'grab';
                            });

                            carousel.addEventListener('mouseup', () => {
                                isDragging = false;
                                carousel.style.cursor = 'grab';
                                carousel.style.userSelect = '';
                            });

                            carousel.addEventListener('touchend', () => {
                                isDragging = false;
                            });

                            carousel.addEventListener('mousemove', (e) => {
                                if (!isDragging) return;
                                e.preventDefault();
                                const x = e.pageX - carousel.offsetLeft;
                                const walk = (x - startX) * 2;
                                carousel.scrollLeft = scrollLeft - walk;
                            });

                            carousel.addEventListener('touchmove', (e) => {
                                if (!isDragging) return;
                                e.preventDefault();
                                const x = e.touches[0].pageX - carousel.offsetLeft;
                                const walk = (x - startX) * 2;
                                carousel.scrollLeft = scrollLeft - walk;
                            });

                            // Cập nhật nút khi scroll
                            carousel.addEventListener('scroll', updateButtons);

                            // Resize
                            let resizeTimer;
                            window.addEventListener('resize', () => {
                                clearTimeout(resizeTimer);
                                resizeTimer = setTimeout(updateButtons, 250);
                            });

                            // Khởi tạo
                            updateButtons();
                            carousel.style.cursor = 'grab';
                        });
                    })();
                    </script>
                    @else
                        <div class="autosensor_single_info_specifications_desc" data-nosnippet>
                            <h2 class="autosensor_single_info_specifications_desc_title">
                                🎁 Ưu đãi khi mua thiết bị tại {{ $settings->site_name ?? 'AutoSensor Việt Nam' }}
                            </h2>
                            <ul class="autosensor_single_info_specifications_desc_list">
                                <li class="autosensor_single_info_specifications_desc_item">
                                    <span class="autosensor_single_info_specifications_desc_number">1</span>
                                    <strong>Bảo hành chính hãng</strong> theo tiêu chuẩn nhà sản xuất, có chứng nhận CO/CQ đầy đủ.
                                </li>
                                <li class="autosensor_single_info_specifications_desc_item">
                                    <span class="autosensor_single_info_specifications_desc_number">2</span>
                                    <strong>Miễn phí tư vấn kỹ thuật</strong> và hỗ trợ thiết kế hệ thống tự động hóa.
                                </li>
                                <li class="autosensor_single_info_specifications_desc_item">
                                    <span class="autosensor_single_info_specifications_desc_number">3</span>
                                    Giảm <strong>5–10%</strong> khi mua combo thiết bị cùng hệ thống hoặc đơn hàng số lượng lớn.
                                </li>
                                <li class="autosensor_single_info_specifications_desc_item">
                                    <span class="autosensor_single_info_specifications_desc_number">4</span>
                                    <strong>Miễn phí vận chuyển</strong> cho đơn hàng từ 5.000.000đ tại khu vực nội thành.
                                </li>
                            </ul>

                            @if ($product->isInFlashSale())
                                @php
                                    $currentFlashSale = $product->currentFlashSale()->first();
                                @endphp
                                @if ($currentFlashSale)
                                    <div class="autosensor_single_info_specifications_desc_flashsale">
                                        <strong>⚡ Flash Sale: {{ $currentFlashSale->title }}</strong><br>
                                        Diễn ra từ
                                        <span class="time">
                                            {{ \Carbon\Carbon::parse($currentFlashSale->start_time)->format('H:i') }}
                                            –
                                            {{ \Carbon\Carbon::parse($currentFlashSale->end_time)->format('H:i') }}
                                        </span>
                                        ngày
                                        <span class="date">
                                            {{ \Carbon\Carbon::parse($currentFlashSale->start_time)->format('d/m') }}
                                        </span>.
                                        <br>
                                        ⚡ Số lượng thiết bị trong đợt Flash Sale có hạn, ưu tiên đơn thanh toán online.<br>
                                        ⚠️ Mỗi khách hàng chỉ mua tối đa 1 sản phẩm cùng loại trong chương trình.<br>
                                        🕒 Đơn hàng giữ trong 24h, không áp dụng kèm các khuyến mãi khác.
                                    </div>
                                @endif
                            @endif
                        </div>
                    @endif

                </div>

                <div class="autosensor_single_info_policy" data-nosnippet>
                    <!-- CSKH Team -->
                    <h3 class="autosensor_single_info_policy_title">ĐỘI NGŨ CSKH</h3>
                    <p class="autosensor_single_info_policy_subtitle">Liên hệ đội ngũ CSKH để được hỗ trợ tốt nhất</p>
                    <div class="autosensor_single_info_policy_cskh">
                        @foreach(($supportStaff ?? collect()) as $support)
                            <div class="autosensor_single_info_policy_cskh_item" style="background: {{ $support->color ?? '#f9f9f9' }};">
                                <div class="cskh-info">
                                @if($support->avatar)
                                        <div class="cskh-avatar">
                                        <img src="{{ asset('clients/assets/img/avatars/' . $support->avatar) }}" alt="{{ $support->name }}"
                                             onerror="this.onerror=null;this.src='{{ asset('clients/assets/img/clothes/no-image.webp') }}'">
                                        </div>
                                    @endif
                                    <div class="cskh-info-content">
                                        <div class="cskh-name">{{ $support->name }}</div>
                                        <div class="cskh-role">{{ $support->role }}</div>
                                    </div>
                                </div>
                                <div class="cskh-contact">
                                    @if($support->phone)
                                        <a class="cskh-phone" href="tel:{{ $support->phone }}">📞 {{ $support->phone }}</a>
                                    @endif
                                    @if($support->zalo)
                                        <a class="cskh-zalo" href="https://zalo.me/{{ $support->zalo }}" target="_blank">Zalo</a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        @if(($supportStaff ?? collect())->isEmpty())
                            <p>Đang cập nhật đội ngũ CSKH.</p>
                        @endif
                    </div>

                    <h3 class="autosensor_single_info_policy_title">CHÍNH SÁCH BÁN HÀNG</h3>
                    <p class="autosensor_single_info_policy_subtitle">Áp dụng cho từng ngành hàng</p>

                    <!-- MIỄN PHÍ VẬN CHUYỂN -->
                    <div class="autosensor_single_info_policy_item">
                        <div class="autosensor_single_info_policy_icon">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="#444"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M20 8h-3V4H3v13h2a3 3 0 1 0 6 0h4a3 3 0 1 0 6 0h1v-5l-4-4zM5 15V6h10v9H5zm13 1a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-10 1a1 1 0 1 1 0-2 1 1 0 0 1 0 2zm10-4V9.4l2.6 2.6H18z" />
                            </svg>
                        </div>
                        <div class="autosensor_single_info_policy_content">
                            <strong>MIỄN PHÍ VẬN CHUYỂN</strong>
                        </div>
                    </div>

                    <!-- ĐỔI TRẢ MIỄN PHÍ -->
                    <div class="autosensor_single_info_policy_item">
                        <div class="autosensor_single_info_policy_icon">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="#444"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6a6 6 0 1 1-12 0H4a8 8 0 1 0 8-8z" />
                            </svg>
                        </div>
                        <div class="autosensor_single_info_policy_content">
                            <strong>ĐỔI TRẢ MIỄN PHÍ</strong>
                        </div>
                    </div>

                    <!-- THANH TOÁN -->
                    <div class="autosensor_single_info_policy_item">
                        <div class="autosensor_single_info_policy_icon">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="#444"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M20 4H4c-1.1 0-2 .9-2 2v3h20V6c0-1.1-.9-2-2-2zm0 5H2v9c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V9zm-6 6H6v-2h8v2z" />
                            </svg>
                        </div>
                        <div class="autosensor_single_info_policy_content">
                            <strong>THANH TOÁN</strong>
                        </div>
                    </div>

                    <!-- HỖ TRỢ MUA NHANH -->
                    <div class="autosensor_single_info_policy_item">
                        <div class="autosensor_single_info_policy_icon">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="#444"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M6.62 10.79a15.055 15.055 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1.01-.24 11.36 11.36 0 0 0 3.58.57 1 1 0 0 1 1 1v3.5a1 1 0 0 1-1 1C9.27 21 3 14.73 3 7.5a1 1 0 0 1 1-1H7.5a1 1 0 0 1 1 1c0 1.25.2 2.47.57 3.58a1 1 0 0 1-.24 1.01l-2.2 2.2z" />
                            </svg>
                        </div>
                        <div class="autosensor_single_info_policy_content">
                            <strong>HỖ TRỢ MUA NHANH</strong>
                            <p><span class="autosensor_single_info_policy_hotline">Call:
                                    {{ preg_replace('/(\d{4})(\d{3})(\d{3})/', '$1.$2.$3', $settings->contact_phone ?? '0382941465') }}
                                    - Zalo:
                                    {{ preg_replace('/(\d{4})(\d{3})(\d{3})/', '$1.$2.$3', $settings->contact_zalo ?? '0382941465') }}</span><br>từ
                                8:00 - 17:00 mỗi ngày (trừ Chủ nhật và ngày lễ).</p>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; justify-content: center; margin: 1rem 0;">
                        <hr style="flex: 1; height: 2px; background-color: #e6525e; border: none; margin: 0;">
                        <span style="padding: 0 12px; color: #f74a4a; font-weight: bold;">Khuyễn mãi & Ưu đãi</span>
                        <hr style="flex: 1; height: 2px; background-color: #e6525e; border: none; margin: 0;">
                    </div>

                    <div class="autosensor_single_info_voucher"
                        style="font-family: Arial, sans-serif; font-size: 14px; line-height: 1.8; width: fit-content; max-width: 100%; margin: auto; text-align: start;">
                        @foreach ($vouchers as $voucher)
                            @php
                                $type = $voucher->type ?? '';
                                $code = $voucher->code ?? '';
                                $value = $voucher->value ?? '';
                                $min = $voucher->min_order_amount ?? '';
                                $max = $voucher->max_discount_amount ?? '';
                            @endphp

                            @if ($type === 'free_ship')
                                <p style="margin:4px 0;font-size:14px;">
                                    🎫 Nhập mã <strong>{{ $code }}</strong> MIỄN PHÍ SHIP
                                    @if ($value)
                                        TỐI ĐA <span style="color:red">{{ number_format($value, 0, ',', '.') }}đ</span>
                                    @endif
                                    @if ($min)
                                        CHO ĐƠN TỪ <span style="color:red">{{ number_format($min, 0, ',', '.') }}đ</span>
                                    @endif
                                </p>
                            @elseif ($type === 'percentage')
                                <p style="margin:4px 0;font-size:14px;">
                                    🎫 Nhập mã <strong>{{ $code }}</strong> GIẢM <span
                                        style="color:red">{{ number_format($value, 0, ',', '.') }}%</span>
                                    @if ($max)
                                        TỐI ĐA <span style="color:red">{{ number_format($max, 0, ',', '.') }}đ</span>
                                    @endif
                                    @if ($min)
                                        CHO ĐƠN TỪ <span style="color:red">{{ number_format($min, 0, ',', '.') }}đ</span>
                                    @endif
                                </p>
                            @elseif ($type === 'fixed_amount')
                                <p style="margin:4px 0;font-size:14px;">
                                    🎫 Nhập mã <strong>{{ $code }}</strong> GIẢM <span
                                        style="color:red">{{ number_format($value, 0, ',', '.') }}</span>
                                    @if ($min)
                                        CHO ĐƠN TỪ <span style="color:red">{{ number_format($min, 0, ',', '.') }}đ</span>
                                    @endif
                                </p>
                            @endif
                        @endforeach

                        <p style="margin: 4px 0; font-size: 14px;"><span>🚚</span> <strong
                                style="font-size: 14px;">FREESHIP 100%</strong> đơn từ 5000K trong nội thành Hải Phòng</p>

                        @if($vouchers || $vouchers->isNotEmpty())
                            <div class="autosensor_single_info_voucher_code" style="margin-top: 16px;">
                                <p style="margin-bottom: 8px;">Mã giảm giá bạn có thể sử dụng:</p>
                                <div style="display: flex; gap: 8px; flex-wrap: wrap; justify-content: center;">
                                    @foreach ($vouchers as $voucher)
                                        <div class="autosensor_single_info_voucher_code_item"
                                            style="background: #000; color: #00ffff; padding: 6px 12px; border-radius: 8px; font-weight: bold; font-size: 13px; font-family: monospace; clip-path: polygon(10% 0%, 90% 0%, 90% 35%, 100% 50%, 90% 65%, 90% 100%, 10% 100%, 10% 65%, 0% 50%, 10% 35%); cursor: pointer;">
                                            {{ $voucher->code ?? 'AUTOSENSOR2025' }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="autosensor_single_info_images_main_overlay">
                <div class="autosensor_single_info_images_main_overlay_images">
                    @forelse ($overlayImages as $img)
                        <div class="autosensor_single_info_images_main_overlay_image">
                            <img src="{{ asset('clients/assets/img/clothes/' . ($img->url ?? 'no-image.webp')) }}"
                                 alt="{{ $img->alt ?? ($product->name ?? 'AutoSensor Việt Nam') }}"
                                 loading="lazy"
                                 onerror="this.onerror=null;this.src='{{ asset('clients/assets/img/clothes/no-image.webp') }}'">
                        </div>
                    @empty
                        <div class="autosensor_single_info_images_main_overlay_image">
                            <img src="{{ asset('clients/assets/img/clothes/no-image.webp') }}"
                                 alt="{{ $product->name ?? 'AutoSensor Việt Nam' }}"
                                 onerror="this.onerror=null;this.src='{{ asset('clients/assets/img/clothes/no-image.webp') }}'">
                        </div>
                    @endforelse
                </div>
            </div>
            <div id="autosensor_main_tab_guide" style="display: flex; align-items: center; justify-content: center; margin: 1rem 0;">
                <hr style="flex: 1; height: 2px; background-color: #e6525e; border: none; margin: 0;">
                <span style="padding: 0 12px; color: #f74a4a; font-weight: bold;">Mô tả sản phẩm</span>
                <hr style="flex: 1; height: 2px; background-color: #e6525e; border: none; margin: 0;">
            </div>
        </section>

        <!-- Mô tả sản phẩm -->
        <section id="autosensor_review">
            <div class="autosensor_single_desc">
                <div class="autosensor_single_desc_button">
                    <button class="autosensor_single_desc_button_describe .autosensor_single_desc_button_active">Thông số</button>
                    <button class="autosensor_single_desc_button_add_info">@desktop Tải Catalog @enddesktop @mobile Catalog @endmobile</button>
                    <button class="autosensor_single_desc_button_reviews">Đánh giá</button>
                </div>
                <div class="autosensor_single_desc_tabs">
                    <div class="autosensor_single_desc_tabs_describe .autosensor_single_desc_tabs_active">
                        <div class="autosensor_single_desc_tabs_describes">
                            <div class="autosensor_single_desc_tabs_describe_specifications">

                                {!! $product->description ?? '<p>Chưa có mô tả cho sản phẩm này.</p>' !!}

                                <p>{!! $product->short_description ?? '' !!}</p>

                                <div class="autosensor_single_info_images_tags">
                                    <h5 class="autosensor_single_info_images_tags_title">Thẻ: </h5>
                                    @if ($product->tags?->isNotEmpty())
                                        @foreach ($product->tags as $tag)
                                            <a href="{{ route('client.shop.index', ['tags' => $tag->slug]) }}" title="Xem tất cả sản phẩm có thẻ {{ $tag->name }}">
                                                <span class="autosensor_single_info_images_tags_tag">#{{ $tag->name ?? 'thoi-trang' }}</span>
                                            </a>
                                        @endforeach
                                    @endif
                                </div>

                                {{-- FAQS --}}
                                @include('clients.templates.faqs')
                            </div>
                            <aside class="autosensor_single_sidebar">
                                <div class="sticky-box">
                                    {{-- Wizard Button --}}
                                    @php
                                        $wizardCategoryId = null;
                                        if ($product->primaryCategory) {
                                            $primaryCat = $product->primaryCategory;
                                            // Nếu là category cha, dùng luôn
                                            if ($primaryCat->parent_id === null) {
                                                $wizardCategoryId = $primaryCat->id;
                                            } elseif ($primaryCat->parent) {
                                                // Nếu là category con, dùng category cha
                                                $wizardCategoryId = $primaryCat->parent->id;
                                            }
                                        }
                                        // Nếu không có, lấy category cha đầu tiên
                                        if (!$wizardCategoryId) {
                                            $firstParentCategory = \App\Models\Category::where('is_active', true)
                                                ->whereNull('parent_id')
                                                ->orderBy('order')
                                                ->orderBy('name')
                                                ->first();
                                            $wizardCategoryId = $firstParentCategory ? $firstParentCategory->id : null;
                                        }
                                    @endphp
                                    @if($wizardCategoryId)
                                    <div class="autosensor_single_sidebar_wizard">
                                        <div class="autosensor_single_sidebar_wizard_header">
                                            <div class="autosensor_single_sidebar_wizard_icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="28" height="28">
                                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                                </svg>
                                            </div>
                                            <div class="autosensor_single_sidebar_wizard_text">
                                                <h3 class="autosensor_single_sidebar_wizard_title">Hướng dẫn chọn sản phẩm</h3>
                                                <p class="autosensor_single_sidebar_wizard_description">Trả lời các câu hỏi để nhận gợi ý sản phẩm phù hợp nhất</p>
                                            </div>
                                        </div>
                                        <a href="{{ route('client.wizard.index', ['category_id' => $wizardCategoryId]) }}" 
                                           class="autosensor_single_sidebar_wizard_button">
                                            <span>Bắt đầu tư vấn</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
                                                <path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/>
                                            </svg>
                                        </a>
                                    </div>
                                    @endif

                                    @include('clients.templates.product_featured')
                                    @include('clients.templates.sidebar_video_catalog')
                                    @include('clients.templates.sidebar_support_info')
                                </div>
                            </aside>
                        </div>
                    </div>

                    <div class="autosensor_single_desc_tabs_add_info">
                        @include('clients.templates.catalog')
                    </div>
                    <div class="autosensor_single_desc_tabs_reviews">
                        @include('clients.partials.comments', [
                            'type' => 'product',
                            'objectId' => $product->id,
                            'comments' => $comments ?? null,
                            'ratingStats' => $ratingStats ?? null,
                            'totalComments' => $totalComments ?? 0
                        ])
                    </div>
                </div>
            </div>
        </section>

        {{-- Sản phẩm liên quan --}}
        @include('clients.templates.product_related')

        <section>
            {{-- Thanh thêm nhanh ở đáy màn hình --}}
            <div class="autosensor_single_add_to_cart_bottom" id="autosensor_single_add_to_cart_bottom">
                <div class="autosensor_single_add_to_cart_bottom_container">
                    <button type="button" class="autosensor_single_add_to_cart_bottom_close" onclick="closeBottomCartBar()" aria-label="Đóng" title="Đóng">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" width="18" height="18" fill="currentColor">
                            <path d="M324.5 411.1c6.2 6.2 16.4 6.2 22.6 0s6.2-16.4 0-22.6L214.6 256 347.1 123.5c6.2-6.2 6.2-16.4 0-22.6s-16.4-6.2-22.6 0L192 233.4 59.5 100.9c-6.2-6.2-16.4-6.2-22.6 0s-6.2 16.4 0 22.6L169.4 256 36.9 388.5c-6.2 6.2-6.2 16.4 0 22.6s16.4 6.2 22.6 0L192 278.6 324.5 411.1z"/>
                        </svg>
                    </button>
                    <div class="autosensor_single_add_to_cart_bottom_price">
                        <div class="autosensor_single_add_to_cart_bottom_image">
                            <img src="{{ asset('clients/assets/img/clothes/' . ($product?->primaryImage?->url ?? 'no-image.webp')) }}" alt="{{ $product?->primaryImage?->alt ?? ($product->name ?? 'AutoSensor Việt Nam') }}" title="{{ $product?->primaryImage?->title ?? ($product->name ?? 'AutoSensor Việt Nam') }}" onerror="this.onerror=null;this.src='{{ asset('clients/assets/img/clothes/no-image.webp') }}'">
                        </div>
                        <div class="autosensor_single_add_to_cart_bottom_price_content">
                            @if($hasVariants)
                                <small id="autosensor_single_add_to_cart_bottom_variant"><strong>{{ $variant->sku ?? 'AutoSensor' }}</strong></small>
                            @else
                                <small id="autosensor_single_add_to_cart_bottom_variant"><strong>{{ $product?->sku ?? 'AutoSensor' }}</strong></small>
                            @endif
                            <span class="new" id="autosensor_single_add_to_cart_bottom_price_new">
                                @if ($sale && $sale > 0 && $sale < $original)
                                    {{ number_format($sale, 0, ',', '.') }}₫
                                @else
                                    {{ number_format($original, 0, ',', '.') }}₫
                                @endif
                            </span>
                            <span class="old" id="autosensor_single_add_to_cart_bottom_price_old" style="{{ ($sale && $sale > 0 && $sale < $original) ? '' : 'display:none;' }}">
                                {{ number_format($original, 0, ',', '.') }}₫
                            </span>
                            <span class="stock" id="autosensor_single_add_to_cart_bottom_stock">
                                @if ($isOutOfStock)
                                    Hết hàng
                                @else
                                    @if($hasVariants)
                                        Còn hàng
                                    @else
                                        Còn {{ max(0, (int) ($quantityProductDetail ?? 0)) }} sản phẩm
                                    @endif
                                @endif
                            </span>
                        </div>
                    </div>

                    <div class="autosensor_single_add_to_cart_bottom_qty" id="autosensor_single_add_to_cart_bottom_qty"
                        data-max-stock="{{ $hasVariants ? 9999 : max(1, (int) ($quantityProductDetail ?? 1)) }}">
                        <button type="button" onclick="autosensorBottomDecreaseQty()">−</button>
                        <span id="autosensor_single_add_to_cart_bottom_qty_value">1</span>
                        <button type="button" onclick="autosensorBottomIncreaseQty()">+</button>
                    </div>

                    <div class="autosensor_single_add_to_cart_bottom_actions">
                        <button type="button" class="cart" onclick="autosensorBottomAddToCart()" {{ $isOutOfStock ? 'disabled' : '' }}>
                            Thêm vào giỏ
                        </button>
                        <a class="contact" href="https://zalo.me/{{ $settings->contact_zalo ?? '0398951396' }}" target="_blank" rel="nofollow">
                            Liên hệ Zalo
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    @if($popup)
        <div id="voucherPopup" class="autosensor_main_show_popup_overlay">
            <div class="autosensor_main_show_popup_box">
                <button class="autosensor_main_show_popup_close">&times;</button>
                @if($popup->image)
                    <div style="margin-bottom:10px;">
                        <img src="{{ asset('clients/assets/img/popup/' . $popup->image) }}" alt="{{ $popup->title }}" style="width:100%; height:auto; border-radius:8px;" onerror="this.onerror=null;this.src='{{ asset('clients/assets/img/clothes/no-image.webp') }}'">
                    </div>
                @endif
                <h3 style="margin:0 0 8px; font-weight:700; color:#d9252a;">{{ $popup->title }}</h3>
                @if($popup->content)
                    <div class="popup-content" style="font-size:14px; color:#333;">{!! $popup->content !!}</div>
                @endif
                @if($popup->button_text && $popup->button_link)
                    <div style="margin-top:12px;">
                        <a href="{{ $popup->button_link }}" class="popup-btn" style="display:inline-block; padding:10px 16px; background:#ed1c24; color:#fff; border-radius:6px; text-decoration:none; font-weight:600;">
                            {{ $popup->button_text }}
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <div style="display: flex; align-items: center; justify-content: center; margin: 1rem 0;">
        <hr style="flex: 1; height: 2px; background-color: #e6525e; border: none; margin: 0;">
        <span style="padding: 0 12px; color: #f74a4a; font-weight: bold; text-align: center;">Đăng ký Email nhận thông báo từ {{ $settings->subname ?? '' }}</span>
        <hr style="flex: 1; height: 2px; background-color: #e6525e; border: none; margin: 0;">
    </div>

    @include('clients.templates.call')

    <div style="display: flex; align-items: center; justify-content: center; margin: 1rem 0;">
        <hr style="flex: 1; height: 2px; background-color: #e6525e; border: none; margin: 0;">
        <span style="padding: 0 12px; color: #f74a4a; font-weight: bold; text-align: center;">Đăng ký Email nhận thông báo từ {{ $settings->subname ?? '' }}</span>
        <hr style="flex: 1; height: 2px; background-color: #e6525e; border: none; margin: 0;">
    </div>

    <!-- Modal chọn variant cho phụ kiện -->
    <div id="accessory-variant-modal" class="autosensor_variant_modal">
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
                        <img id="accessory-modal-product-image" src="" alt="" onerror="this.onerror=null;this.src='{{ asset('clients/assets/img/clothes/no-image.webp') }}'">
                    </div>
                    <div class="autosensor_variant_modal_product_info">
                        <h3 id="accessory-modal-product-name" class="autosensor_variant_modal_product_name"></h3>
                        <div id="accessory-modal-product-price" class="autosensor_variant_modal_product_price"></div>
                    </div>
                </div>
                <div class="autosensor_variant_modal_variants" id="accessory-modal-variants-section" style="display: none;">
                    <label class="autosensor_variant_modal_variants_label">Chọn biến thể:</label>
                    <div id="accessory-modal-variants-list" class="autosensor_variant_modal_variants_list"></div>
                </div>
                <div class="autosensor_variant_modal_quantity">
                    <label class="autosensor_variant_modal_quantity_label" for="accessory-modal-quantity">Số lượng:</label>
                    <div class="autosensor_variant_modal_quantity_controls">
                        <button type="button" class="autosensor_variant_modal_quantity_btn" data-action="decrease" aria-label="Giảm số lượng">-</button>
                        <input type="number" id="accessory-modal-quantity" value="1" min="1" class="autosensor_variant_modal_quantity_input" aria-label="Số lượng sản phẩm">
                        <button type="button" class="autosensor_variant_modal_quantity_btn" data-action="increase" aria-label="Tăng số lượng">+</button>
                    </div>
                </div>
                <div class="autosensor_variant_modal_actions">
                    <button type="button" class="autosensor_variant_modal_btn autosensor_variant_modal_btn_secondary" id="accessory-modal-cancel-btn">Hủy</button>
                    <button type="button" class="autosensor_variant_modal_btn autosensor_variant_modal_btn_primary" id="accessory-modal-add-to-cart-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" width="18" height="18" style="margin-right: 8px;">
                            <path fill="currentColor" d="M0 24C0 10.7 10.7 0 24 0L69.5 0c22 0 41.5 12.8 50.6 32l411 0c26.3 0 45.5 25 38.6 50.4l-41 152.3c-8.5 31.4-37 53.3-69.5 53.3l-288.5 0 5.4 28.5c2.2 11.3 12.1 19.5 23.6 19.5L488 336c13.3 0 24 10.7 24 24s-10.7 24-24 24l-288.3 0c-34.6 0-64.3-24.6-70.7-58.5L77.4 54.5c-.7-3.8-4-6.5-7.9-6.5L24 48C10.7 48 0 37.3 0 24zM128 464a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zm336-48a48 48 0 1 1 0 96 48 48 0 1 1 0-96z"/>
                        </svg>
                        Thêm vào giỏ hàng
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
