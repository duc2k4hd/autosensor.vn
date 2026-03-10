@extends('clients.layouts.master')

@section('title', $pageTitle)

@section('head')
    <link rel="stylesheet" href="{{ asset('clients/assets/css/shop.css?v='.$v) }}">
    <link rel="stylesheet" href="{{ asset('clients/assets/css/shop-modal.css?v='.$v) }}">

    <!-- 🔑 Keywords -->
    <meta name="keywords" content="{{ $pageKeywords }}">

    <!-- 📝 Description -->
    <meta name="description" content="{{ $pageDescription }}">

    <!-- 🤖 Robots -->
    {{-- @php
        $productCount = $productsMain->total() ?? 0;
    @endphp --}}
    @php
        // Noindex cho URL filter (query string) hỗn hợp
        // Index cho URL đẹp: /cam-bien-omron, /cam-bien, /cam-bien-tiem-can-panasonic
        $hasFilterQuery = request()->has('category') || 
                         request()->has('keyword') || 
                         request()->has('tags') || 
                         (request()->has('brands') && !isset($brand)) || // Có brands query string mà không phải trang Landing Page brand
                         request()->has('minPriceRange') ||
                         request()->has('maxPriceRange') ||
                         request()->has('minRating') ||
                         request()->has('sort') ||
                         request()->has('perPage');
        
        // Nếu là category-brand page (URL đẹp) hoặc Category page (URL đẹp) → index
        // Nếu có các query string lọc phức tạp → noindex để tránh duplicate content
        $shouldIndex = !$hasFilterQuery;
    @endphp
    @if (!$shouldIndex)
        <meta name="robots" content="noindex, follow" />
    @else
        <meta name="robots" content="index, follow, max-snippet:-1, max-video-preview:-1, max-image-preview:large" />
    @endif

    <!-- 📅 Date -->
    <meta http-equiv="date" content="{{ now()->format('d/m/Y') }}" />

    <!-- 🌐 Open Graph -->
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $pageImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ $pageTitle }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $settings->site_name ?? $settings->subname ?? 'AutoSensor Việt Nam' }}">
    <meta property="og:locale" content="vi_VN">

    <!-- 🐦 Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ $pageImage }}">
    <meta name="twitter:creator" content="{{ $settings->site_name ?? $settings->subname ?? 'AutoSensor Việt Nam' }}">

    <!-- 🔗 Canonical & hreflang -->
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <link rel="alternate" hreflang="vi" href="{{ $canonicalUrl }}">
    <link rel="alternate" hreflang="x-default" href="{{ $canonicalUrl }}">
@endsection


@section('foot')
    <script>
        // AI chat context for category/shop pages
        window.aiPageContext = {
            page: @json($category ? 'category' : 'shop'),
            url: window.location.href,
            title: document.title || @json($pageTitle ?? 'Cửa hàng'),
            category_id: @json($category->id ?? null),
            category_slug: @json($category->slug ?? null),
            category_name: @json($category->name ?? null),
            category_ids: @json($category ? [$category->id] : []),
        };
    </script>
    <script src="{{ asset('clients/assets/js/shop.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            // Xử lý form thêm vào giỏ hàng (sản phẩm không có variant)
            const addToCartForms = document.querySelectorAll('.add-to-cart-form');
            
            addToCartForms.forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    const submitBtn = form.querySelector('.add-to-cart-btn');
                    const quantityInput = form.querySelector('input[name="quantity"]');
                    const quantity = parseInt(quantityInput.value) || 1;
                    
                    // Validate số lượng
                    if (quantity < 1) {
                        e.preventDefault();
                        alert('Số lượng phải lớn hơn 0');
                        quantityInput.focus();
                        return false;
                    }
                    
                    // Disable button và hiển thị loading
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        const originalText = submitBtn.innerHTML;
                        submitBtn.innerHTML = '<span>Đang thêm...</span>';
                        
                        // Re-enable sau 3 giây nếu có lỗi (fallback)
                        setTimeout(function() {
                            if (submitBtn.disabled) {
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = originalText;
                            }
                        }, 3000);
                    }
                });
            });

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

            // Hàm format currency
            function formatCurrencyVND(amount) {
                if (isNaN(amount)) return '0';
                return Number(amount).toLocaleString('vi-VN');
            }

            // Mở modal
            
            if (openModalBtns.length === 0) {
                const allButtons = document.querySelectorAll('button');
                allButtons.forEach(function(btn, idx) {
                    if (btn.textContent && btn.textContent.includes('Thêm vào giỏ')) {
                        
                    }
                });
            }
            
            openModalBtns.forEach(function(btn, index) {
                // Test click immediately
                
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const productId = btn.dataset.productId;
                    const productName = btn.dataset.productName;
                    const productImage = btn.dataset.productImage;
                    const productPrice = parseFloat(btn.dataset.productPrice);
                    const productSalePrice = btn.dataset.productSalePrice ? parseFloat(btn.dataset.productSalePrice) : null;
                    let variants = [];
                    
                    try {
                        const variantsStr = btn.dataset.variants || '[]';
                        variants = JSON.parse(variantsStr);
                    } catch (e) {
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
                    this.innerHTML = '<span>Đang thêm...</span>';

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

        // === Thu gọn / Xem thêm mô tả danh mục ===
        (function () {
            function initDescToggle() {
                var COLLAPSE_HEIGHT = 200; // px - chiều cao thu gọn
                var THRESHOLD     = 220; // chỉ bật toggle khi nội dung cao hơn mức này

                document.querySelectorAll('.js-desc-text').forEach(function (textEl) {
                    if (textEl.dataset.descInit) return; // tránh init lại
                    textEl.dataset.descInit = '1';

                    var toggle = textEl.parentElement.querySelector('.js-desc-toggle');
                    if (!toggle) return;

                    // Đo chiều cao thật của nội dung
                    var fullHeight = textEl.scrollHeight;

                    if (fullHeight <= THRESHOLD) return; // ngắn → không cần toggle

                    // Khởi tạo trạng thái thu gọn
                    textEl.style.maxHeight = COLLAPSE_HEIGHT + 'px';
                    textEl.classList.add('is-collapsed');
                    toggle.style.display = '';
                    toggle.setAttribute('aria-expanded', 'false');

                    toggle.addEventListener('click', function () {
                        var collapsed = textEl.classList.contains('is-collapsed');

                        if (collapsed) {
                            // Mở ra: set max-height về chiều cao thật để animation chạy
                            textEl.style.maxHeight = fullHeight + 'px';
                            textEl.classList.remove('is-collapsed');
                            toggle.classList.add('is-expanded');
                            toggle.setAttribute('aria-expanded', 'true');
                            toggle.querySelector('.js-desc-label').textContent = 'Thu gọn';

                            // Sau khi animation xong → bỏ giới hạn để nội dung dynamic không bị cắt
                            textEl.addEventListener('transitionend', function onEnd() {
                                if (!textEl.classList.contains('is-collapsed')) {
                                    textEl.style.maxHeight = 'none';
                                }
                                textEl.removeEventListener('transitionend', onEnd);
                            });
                        } else {
                            // Đặt lại max-height trước animation thu gọn
                            textEl.style.maxHeight = textEl.scrollHeight + 'px';
                            // Force reflow
                            textEl.getBoundingClientRect();
                            // Rồi mới set về giá trị thu gọn
                            requestAnimationFrame(function () {
                                textEl.style.maxHeight = COLLAPSE_HEIGHT + 'px';
                                textEl.classList.add('is-collapsed');
                                toggle.classList.remove('is-expanded');
                                toggle.setAttribute('aria-expanded', 'false');
                                toggle.querySelector('.js-desc-label').textContent = 'Xem thêm';
                            });

                            // Cuộn lên đầu vùng mô tả cho tiện
                            setTimeout(function () {
                                textEl.closest('.autosensor_shop_category_description').scrollIntoView({
                                    behavior: 'smooth', block: 'nearest'
                                });
                            }, 520);
                        }
                    });
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initDescToggle);
            } else {
                initDescToggle();
            }
        })();

    </script>
@endsection

@section('schema')
    @include('clients.templates.schema_shop', [
        'products' => $productsMain,
        'category' => $category ?? null,
    ])
@endsection

@section('content')
    <!-- Breadcrumb -->
    <section>
        <div class="autosensor_shop_breadcrumb">
            <a href="{{ route('client.home.index') }}">Trang chủ</a>
            <span class="separator">></span>

            @if ($category)
                @php
                    // Tạo breadcrumb path từ danh mục hiện tại lên danh mục gốc
                    $breadcrumbPath = collect();
                    $currentCategory = $category;

                    while ($currentCategory) {
                        $breadcrumbPath->prepend($currentCategory);
                        $currentCategory = $currentCategory->parent;
                    }
                @endphp

                @foreach ($breadcrumbPath as $breadcrumb)
                    @if ($loop->last)
                        <span class="breadcrumb-current">{{ $breadcrumb->name }}</span>
                    @else
                        <a href="{{ url('/'.$breadcrumb->slug) }}">{{ $breadcrumb->name }}</a>
                        <span class="separator">></span>
                    @endif
                @endforeach
            @else
                <span>Cửa hàng</span>
            @endif
        </div>
    </section>
    <main class="autosensor_shop">

        <!-- Banner -->
        {{-- <section>
            <div class="autosensor_shop_banner">
                @if ($banner && $banner->count() > 0)
                    <img class="autosensor_shop_banner_image"
                        src="{{ asset('clients/assets/img/banners/' . $banner->image) }}" alt="{{ $banner->title }}">
                @endif
            </div>
        </section> --}}

        <!-- Bộ lọc -->
        <section>
            @php
                $currentSort = $sort ?? 'default';
            @endphp
            <div class="autosensor_shop_products">
                <div class="autosensor_shop_products_filter">
                    <div class="autosensor_shop_products_filter_categories">
                        <div class="autosensor_shop_products_filter_categories_title">
                            <h3 class="autosensor_shop_products_filter_categories_title_name">Lọc sản phẩm</h3>
                            <div class="autosensor_shop_products_filter_categories_title_bars">
                                <svg focusable="false" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24">
                                    <path d="M3 6h18v2H3V6zm0 5h18v2H3v-2zm0 5h18v2H3v-2z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Bộ lọc hãng -->
                        <div class="autosensor_shop_products_filter_brands">
                            <h4 class="autosensor_shop_products_filter_brands_title">Lọc theo hãng</h4>
                            <div class="autosensor_shop_products_filter_brands_content">
                                @if (!empty($allBrands) && $allBrands->count() > 0)
                                    <div class="autosensor_shop_products_filter_brands_list">
                                        @foreach ($allBrands as $brandItem)
                                            @php
                                                $isSelected = in_array($brandItem->slug, $selectedBrandSlugs ?? []);
                                                
                                                // Link thông minh:
                                                // Nếu có category, link tới Landing Page Category-Brand
                                                // Nếu không, link tới shop index với filter brand
                                                if (!empty($category)) {
                                                    $brandLink = route('client.shop.category-brand', [
                                                        'categorySlug' => $category->slug,
                                                        'brandSlug' => $brandItem->slug,
                                                        'keyword' => $keyword ?: null
                                                    ]);
                                                } else {
                                                    $brandLink = route('client.shop.index', [
                                                        'brands' => $brandItem->slug,
                                                        'category' => $category?->slug,
                                                        'keyword' => $keyword ?: null
                                                    ]);
                                                }
                                            @endphp
                                            <a href="{{ $brandLink }}" 
                                               class="autosensor_shop_products_filter_brands_item {{ $isSelected ? 'autosensor_shop_products_filter_brands_item_active' : '' }}"
                                               style="display: flex; justify-content: space-between; align-items: center; text-decoration: none; color: inherit; margin-bottom: 8px; font-size: 14px;">
                                                <span class="autosensor_shop_products_filter_brands_item_name">{{ $brandItem->name }}</span>
                                                <span class="autosensor_shop_products_filter_brands_item_count" style="color: #888; font-size: 12px;">({{ $brandItem->products_count }})</span>
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="autosensor_shop_products_filter_brands_empty">Chưa có hãng nào</p>
                                @endif
                            </div>
                        </div>

                        <div class="autosensor_shop_products_filter_categories_form">
                            <!-- Bộ lọc giá -->
                            <div class="autosensor_shop_products_filter_price">
                                <h4 class="autosensor_shop_products_filter_price_title">Lọc theo giá</h4>
                                <div class="autosensor_shop_products_filter_price_content">
                                    <form id="autosensor_shop_products_filter_price_content_form"
                                        action="{{ request()->url() }}" method="GET"
                                        class="autosensor_shop_products_filter_price_form">
                                        {{-- Giữ lại các filter hiện tại --}}
                                        <input type="hidden" name="page" value="1">
                                        <input type="hidden" name="perPage" value="{{ $perPage ?? 30 }}">
                                        <input type="hidden" name="sort" value="{{ $currentSort }}">
                                        @if (!empty($keyword))
                                            <input type="hidden" name="keyword" value="{{ $keyword }}">
                                        @endif
                                        @if (!empty($selectedCategorySlug))
                                            <input type="hidden" name="category" value="{{ $selectedCategorySlug }}">
                                        @endif
                                        @isset($minRating)
                                            <input type="hidden" name="minRating" value="{{ $minRating }}">
                                        @endisset
                                        @if (!empty($tags))
                                            @foreach ($tags as $tagId)
                                                <input type="hidden" name="tags[]" value="{{ $tagId }}">
                                            @endforeach
                                        @endif
                                        @if (!empty($selectedBrandSlugs))
                                            <input type="hidden" name="brands" value="{{ implode(',', $selectedBrandSlugs) }}">
                                        @endif
    
                                        {{-- Đây là input sẽ được gán giá trị bằng JS --}}
                                        <input type="hidden" name="minPriceRange" id="minPriceRange"
                                            value="{{ $minPriceRange }}">
                                        <input type="hidden" name="maxPriceRange" id="maxPriceRange"
                                            value="{{ $maxPriceRange }}">
    
                                        <label
                                            class="autosensor_shop_products_filter_price_content_form_label {{ (int) $minPriceRange === 0 && (int) $maxPriceRange === 500000 ? 'autosensor_shop_products_filter_price_content_form_label_active' : '' }}"
                                            onclick="setPrice(0, 500000)">
                                            Dưới 500.000 VNĐ
                                        </label>
    
                                        <label
                                            class="autosensor_shop_products_filter_price_content_form_label {{ (int) $minPriceRange === 500000 && (int) $maxPriceRange === 1000000 ? 'autosensor_shop_products_filter_price_content_form_label_active' : '' }}"
                                            onclick="setPrice(500000, 1000000)">
                                            500.000 - 1.000.000 VNĐ
                                        </label>
    
                                        <label
                                            class="autosensor_shop_products_filter_price_content_form_label {{ (int) $minPriceRange === 1000000 && (int) $maxPriceRange === 2000000 ? 'autosensor_shop_products_filter_price_content_form_label_active' : '' }}"
                                            onclick="setPrice(1000000, 2000000)">
                                            1.000.000 - 2.000.000 VNĐ
                                        </label>
    
                                        <label
                                            class="autosensor_shop_products_filter_price_content_form_label {{ (int) $minPriceRange === 2000000 && (int) ($maxPriceRange ?? 0) >= 2000000 ? 'autosensor_shop_products_filter_price_content_form_label_active' : '' }}"
                                            onclick="setPrice(2000000, 100000000)">
                                            Trên 2.000.000 VNĐ
                                        </label>
                                    </form>
                                </div>
                            </div>
                        </div>

                        {{-- Bộ lọc danh mục --}}
                        <div class="autosensor_shop_products_filter_categories_content">
                            <h4 class="autosensor_shop_products_filter_categories_title">Lọc theo danh mục</h4>
                            @foreach ($categories as $cat)
                                @php
                                    $isActiveCategory =
                                        ($selectedCategorySlug ?? null) === $cat->slug ||
                                        ($category?->slug ?? null) === $cat->slug ||
                                        request()->segment(1) === $cat->slug;
                                @endphp
                                <div
                                    class="autosensor_shop_products_filter_categories_content_category {{ $isActiveCategory ? 'autosensor_shop_products_filter_categories_content_category_active' : '' }}">
                                    <div class="autosensor_shop_products_filter_categories_content_category_image">
                                        <a
                                            href="{{ route('client.shop.index', array_filter(['category' => $cat->slug, 'keyword' => $keyword ?: null])) }}">
                                            <img width="30px" height="30px"
                                                class="autosensor_shop_products_filter_categories_content_category_image_img"
                                                src="{{ asset('clients/assets/img/categories/' . ($cat->image ?? 'no-image.webp')) }}"
                                                alt="{{ $cat->name }}">
                                        </a>
                                    </div>
                                    <div class="autosensor_shop_products_filter_categories_content_category_text">
                                        <a
                                            href="{{ route('client.shop.index', array_filter(['category' => $cat->slug, 'keyword' => $keyword ?: null])) }}">
                                            <p>{{ $cat->name }}</p>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
            
                    {{-- Wizard Button --}}
                    @php
                        $wizardCategoryId = null;
                        if ($category && $category->parent_id === null) {
                            // Nếu là category cha, dùng luôn
                            $wizardCategoryId = $category->id;
                        } elseif ($category && $category->parent) {
                            // Nếu là category con, dùng category cha
                            $wizardCategoryId = $category->parent->id;
                        } else {
                            // Lấy category cha đầu tiên
                            $firstParentCategory = \App\Models\Category::where('is_active', true)
                                ->whereNull('parent_id')
                                ->orderBy('order')
                                ->orderBy('name')
                                ->first();
                            $wizardCategoryId = $firstParentCategory ? $firstParentCategory->id : null;
                        }
                    @endphp
                    @if($wizardCategoryId)
                    <div class="autosensor_shop_products_filter_wizard">
                        <div class="autosensor_shop_products_filter_wizard_content">
                            <div class="autosensor_shop_products_filter_wizard_icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                </svg>
                            </div>
                            <div class="autosensor_shop_products_filter_wizard_text">
                                <h4 class="autosensor_shop_products_filter_wizard_title">Hướng dẫn chọn sản phẩm</h4>
                                <p class="autosensor_shop_products_filter_wizard_description">Trả lời các câu hỏi để chúng tôi gợi ý sản phẩm phù hợp nhất</p>
                            </div>
                        </div>
                        <a href="{{ route('client.wizard.index', ['category_id' => $wizardCategoryId]) }}" 
                           class="autosensor_shop_products_filter_wizard_button">
                            <span>Bắt đầu tư vấn</span>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                                <path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/>
                            </svg>
                        </a>
                    </div>
                    @endif

                    <div class="autosensor_shop_products_filter_new_products">
                        <h4 class="autosensor_shop_products_filter_new_products_title">Sản phẩm mới</h4>
                        <div class="autosensor_shop_products_filter_new_products_description">
                            <p>Khám phá những thiết bị tự động hóa mới nhất tại {{ $settings->site_name ?? $settings->subname ?? 'AutoSensor Việt Nam' }}. Chúng tôi luôn cập
                                nhật
                                những sản phẩm mới, chất lượng cao và công nghệ tiên tiến để phục vụ nhu cầu tự động hóa của doanh nghiệp.</p>
                        </div>
                        @if (!empty($newProducts) && $newProducts->count() > 0)
                            @foreach ($newProducts as $product)
                                <div class="autosensor_shop_products_filter_new_products_item">
                                    <div class="autosensor_shop_products_filter_new_products_item_image">
                                        <a href="{{ $product->meta_canonical ?? ($settings->site_url ?? 'https://autosensor.vn'). $product->slug }}">
                                            <img class="autosensor_shop_products_filter_new_products_item_image_img"
                                                src="{{ asset('clients/assets/img/clothes/' . ($product?->primaryImage?->url ?? 'no-image.webp')) }}"
                                                alt="{{ $product?->primaryImage?->alt ?? $product?->name }}"
                                                title="{{ $product?->primaryImage?->title }}">
                                        </a>
                                    </div>
                                    <div class="autosensor_shop_products_filter_new_products_item_info">
                                        <a href="{{ $product->meta_canonical ?? $settings->site_url ?? 'https://autosensor.vn'. $product->slug }}">
                                            <h4 class="autosensor_shop_products_filter_new_products_item_info_title">
                                                {{ $product->name }}</h4>
                                        </a>
                                        <p class="autosensor_shop_products_filter_new_products_item_info_price">
                                            {{ number_format($product->price, 0, ',', '.') }}đ</p>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>

                <div class="autosensor_shop_products_content">
                    <div class="autosensor_shop_products_content_filter">
                        <div class="autosensor_shop_products_content_filters">
                            <div class="autosensor_shop_products_content_filter_total">
                                Tổng <span>{{ $productsMain->total() ?? 0 }}</span> sản phẩm
                            </div>
                            @if (!empty($keyword))
                                <div class="autosensor_shop_products_content_filter_keyword">
                                    Từ khóa: <strong>"{{ $keyword }}"</strong>
                                </div>
                            @endif
                        </div>
                        @if (request()->query())
                            {{-- Có ít nhất 1 bộ lọc đang được áp dụng --}}
                            <div class="autosensor_shop_products_content_filter_delete_all">
                                <button class="autosensor_shop_products_content_filter_delete_all_btn"
                                    onclick="window.location.href='{{ route('client.shop.index') }}'">
                                    Xóa tất cả bộ lọc
                                </button>
                            </div>
                        @endif
                        <div class="autosensor_shop_products_content_filter_select">
                            <div class="autosensor_shop_products_content_filter_select_sort">
                                <label for="sort">Sắp xếp theo:</label>
                                <form action="{{ request()->url() }}" method="GET"
                                    class="autosensor_shop_products_content_filter_select_sort_form">
                                    <input type="hidden" name="page" value="1">
                                    <input type="hidden" name="perPage" value="{{ $perPage ?? 30 }}">
                                    @if (!is_null($minPriceRange))
                                        <input type="hidden" name="minPriceRange" value="{{ $minPriceRange }}">
                                    @endif
                                    @if (!is_null($maxPriceRange))
                                        <input type="hidden" name="maxPriceRange" value="{{ $maxPriceRange }}">
                                    @endif
                                    @if (!empty($keyword))
                                        <input type="hidden" name="keyword" value="{{ $keyword }}">
                                    @endif
                                    @if (!empty($selectedCategorySlug))
                                        <input type="hidden" name="category" value="{{ $selectedCategorySlug }}">
                                    @endif
                                    @isset($minRating)
                                        <input type="hidden" name="minRating" value="{{ $minRating }}">
                                    @endisset
                                    @if (!empty($tags))
                                        @foreach ($tags as $tagId)
                                            <input type="hidden" name="tags[]" value="{{ $tagId }}">
                                        @endforeach
                                    @endif
                                    @if (!empty($selectedBrandSlugs))
                                        <input type="hidden" name="brands" value="{{ implode(',', $selectedBrandSlugs) }}">
                                    @endif
                                    <select name="sort" id="sort" onchange="this.form.submit()">
                                        <option value="default" {{ $currentSort === 'default' ? 'selected' : '' }}>
                                            Mặc định (Mới nhất)
                                        </option>
                                        <option value="newest" {{ $currentSort === 'newest' ? 'selected' : '' }}>
                                            Hàng mới về
                                        </option>
                                        <option value="price-asc" {{ $currentSort === 'price-asc' ? 'selected' : '' }}>
                                            Giá: Thấp đến Cao
                                        </option>
                                        <option value="price-desc" {{ $currentSort === 'price-desc' ? 'selected' : '' }}>
                                            Giá: Cao đến Thấp
                                        </option>
                                        <option value="name-asc" {{ $currentSort === 'name-asc' ? 'selected' : '' }}>
                                            Tên: A → Z
                                        </option>
                                        <option value="name-desc" {{ $currentSort === 'name-desc' ? 'selected' : '' }}>
                                            Tên: Z → A
                                        </option>
                                    </select>
                                </form>
                            </div>

                            <div class="autosensor_shop_products_content_filter_select_show">
                                <label for="show">Hiển thị:</label>
                                <form action="{{ request()->url() }}" method="GET"
                                    class="autosensor_shop_products_content_filter_select_show_form">
                                    {{-- Giữ lại các filter hiện tại --}}
                                    <input type="hidden" name="page" value="1">
                                    <input type="hidden" name="sort" value="{{ $currentSort }}">
                                    @if (!empty($keyword))
                                        <input type="hidden" name="keyword" value="{{ $keyword }}">
                                    @endif
                                    @if (!empty($selectedCategorySlug))
                                        <input type="hidden" name="category" value="{{ $selectedCategorySlug }}">
                                    @endif
                                    @isset($minRating)
                                        <input type="hidden" name="minRating" value="{{ $minRating }}">
                                    @endisset
                                    @if (!empty($tags))
                                        @foreach ($tags as $tagId)
                                            <input type="hidden" name="tags[]" value="{{ $tagId }}">
                                        @endforeach
                                    @endif
                                    @if (!empty($selectedBrandSlugs))
                                        <input type="hidden" name="brands" value="{{ implode(',', $selectedBrandSlugs) }}">
                                    @endif

                                    {{-- Select số sản phẩm --}}
                                    <select name="perPage" id="perPage" onchange="this.form.submit()">
                                        @foreach ([24, 30, 36, 48, 60, 72, 96] as $val)
                                            <option value="{{ $val }}" {{ (int) $perPage === $val ? 'selected' : '' }}>
                                                {{ $val }} sản phẩm
                                            </option>
                                        @endforeach
                                    </select>

                                    @if (isset($minPriceRange))
                                        <input type="hidden" name="minPriceRange" value="{{ $minPriceRange }}">
                                    @endif

                                    @if (isset($maxPriceRange))
                                        <input type="hidden" name="maxPriceRange" value="{{ $maxPriceRange }}">
                                    @endif
                                </form>
                            </div>
                        </div>
                    </div>
                    @if (!empty($productsMain) && $productsMain->count() > 0)
                        <div class="autosensor_shop_products_content_list">
                            @foreach ($productsMain as $product)
                                @php
                                    // Chuẩn bị variants data cho modal
                                    $variantsData = [];
                                    if ($product->variants && $product->variants->isNotEmpty()) {
                                        foreach ($product->variants as $v) {
                                            $attrs = is_array($v->attributes) ? $v->attributes : (is_string($v->attributes) ? json_decode($v->attributes, true) : []);
                                            $details = [];
                                            if (!empty($attrs['size'])) $details[] = $attrs['size'];
                                            if (!empty($attrs['has_pot']) && $attrs['has_pot']) $details[] = 'Có phụ kiện đi kèm';
                                            if (!empty($attrs['combo_type'])) $details[] = $attrs['combo_type'];
                                            if (!empty($attrs['notes'])) $details[] = $attrs['notes'];
                                            $variantsData[] = [
                                                'id' => $v->id,
                                                'name' => $v->name,
                                                'price' => $v->price,
                                                'sale_price' => $v->sale_price,
                                                'display_price' => $v->display_price,
                                                'stock_quantity' => $v->stock_quantity,
                                                'is_active' => $v->is_active,
                                                'details' => $details,
                                                'is_on_sale' => $v->isOnSale(),
                                                'discount_percent' => $v->discount_percent,
                                            ];
                                        }
                                    }
                                @endphp
                                
                                <div class="autosensor_shop_products_content_list_item">
                                    <div class="autosensor_shop_products_content_list_item_label">
                                        {{ $product->label }}
                                    </div>
                                    <div class="autosensor_shop_products_content_list_item_image">
                                        <a href="{{ route('client.product.detail', ['slug' => $product->slug]) }}">
                                            <img class="autosensor_shop_products_content_list_item_image_img"
                                                src="{{ asset('clients/assets/img/clothes/' . ($product?->primaryImage?->url ?? 'no-image.webp')) }}"
                                                alt="{{ $product?->primaryImage?->alt ?? $product?->name }}"
                                                title="{{ $product?->primaryImage?->title ?? $product?->name }}">
                                        </a>
                                    </div>
                                    <div class="autosensor_shop_products_content_list_item_category">
                                        <h5 class="autosensor_shop_products_content_list_item_category_name">
                                            {{ optional($product->brand)->name ?? ($settings->site_name ?? ($settings->subname ?? 'AutoSensor Việt Nam')) }}
                                        </h5>
                                    </div>
                                    <div class="autosensor_shop_products_content_list_item_title">
                                        <a href="{{ route('client.product.detail', ['slug' => $product->slug]) }}">
                                            <h4 class="autosensor_shop_products_content_list_item_title_name">
                                                {{ $product->name }}
                                            </h4>
                                        </a>
                                    </div>
                                    <div class="autosensor_shop_products_content_list_item_star">
                                        <span class="autosensor_shop_products_content_list_item_star_icon">
                                            @php
                                                $star = rand(4, 5);
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
                                        <span class="autosensor_shop_products_content_list_item_star_count">
                                            ({{ rand(5, 1000) }} review)
                                        </span>
                                    </div>
                                    <div class="autosensor_shop_products_content_list_item_price">
                                        @if ($product->sale_price && $product->sale_price < $product->price)
                                            <span class="autosensor_shop_products_content_list_item_price_new">
                                                {{ number_format($product->sale_price, 0, ',', '.') }}đ
                                            </span>
                                            <span class="autosensor_shop_products_content_list_item_price_old">
                                                {{ number_format($product->price, 0, ',', '.') }}đ
                                            </span>
                                        @else
                                            <span class="autosensor_shop_products_content_list_item_price_new">
                                                {{ number_format($product->price ?? 0, 0, ',', '.') }}đ
                                            </span>
                                        @endif
                                    </div>

                                    <div class="autosensor_shop_products_content_list_item_addtocart">
                                        @if(!empty($variantsData))
                                            <button type="button" 
                                                    class="autosensor_shop_products_content_list_item_addtocart_button open-variant-modal-btn" 
                                                    style="width: 100%;" 
                                                    data-product-id="{{ $product->id }}"
                                                    data-product-name="{{ $product->name }}"
                                                    data-product-slug="{{ $product->slug }}"
                                                    data-product-image="{{ asset('clients/assets/img/clothes/' . ($product?->primaryImage?->url ?? 'no-image.webp')) }}"
                                                    data-product-price="{{ $product->price }}"
                                                    data-product-sale-price="{{ $product->sale_price ?? '' }}"
                                                    data-variants='@json($variantsData)'>
                                                <svg focusable="false" aria-hidden="true"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 576 512">
                                                    <path
                                                        d="M0 24C0 10.7 10.7 0 24 0L69.5 0c22 0 41.5 12.8 50.6 32l411 0c26.3 0 45.5 25 38.6 50.4l-41 152.3c-8.5 31.4-37 53.3-69.5 53.3l-288.5 0 5.4 28.5c2.2 11.3 12.1 19.5 23.6 19.5L488 336c13.3 0 24 10.7 24 24s-10.7 24-24 24l-288.3 0c-34.6 0-64.3-24.6-70.7-58.5L77.4 54.5c-.7-3.8-4-6.5-7.9-6.5L24 48C10.7 48 0 37.3 0 24zM128 464a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zm336-48a48 48 0 1 1 0 96 48 48 0 1 1 0-96zM252 160c0 11 9 20 20 20l44 0 0 44c0 11 9 20 20 20s20-9 20-20l0-44 44 0c11 0 20-9 20-20s-9-20-20-20l-44 0 0-44c0-11-9-20-20-20s-20 9-20 20l0 44-44 0c-11 0-20 9-20 20z" />
                                                </svg> Thêm
                                            </button>
                                        @else
                                            <form action="{{ route('client.cart.store') }}" method="POST" class="add-to-cart-form" data-product-id="{{ $product->id }}">
                                                @csrf
                                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                                <div class="quantity-input-group">
                                                    <label hidden for="quantity_{{ $product->id }}"></label>
                                                    <input type="number" 
                                                           name="quantity" 
                                                           id="quantity_{{ $product->id }}" 
                                                           value="1" 
                                                           min="1" 
                                                           max="{{ $product->stock_quantity ?? 999 }}"
                                                           required
                                                           style="width: 40px; padding: 3.5px 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; text-align: center; border: .2px solid rgb(80, 155, 226); outline: none;">
                                                </div>
                                                <button type="submit" class="autosensor_shop_products_content_list_item_addtocart_button add-to-cart-btn" style="width: 100%;" data-product-id="{{ $product->id }}">
                                                    <svg focusable="false" aria-hidden="true"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        viewBox="0 0 576 512">
                                                        <path
                                                            d="M0 24C0 10.7 10.7 0 24 0L69.5 0c22 0 41.5 12.8 50.6 32l411 0c26.3 0 45.5 25 38.6 50.4l-41 152.3c-8.5 31.4-37 53.3-69.5 53.3l-288.5 0 5.4 28.5c2.2 11.3 12.1 19.5 23.6 19.5L488 336c13.3 0 24 10.7 24 24s-10.7 24-24 24l-288.3 0c-34.6 0-64.3-24.6-70.7-58.5L77.4 54.5c-.7-3.8-4-6.5-7.9-6.5L24 48C10.7 48 0 37.3 0 24zM128 464a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zm336-48a48 48 0 1 1 0 96 48 48 0 1 1 0-96zM252 160c0 11 9 20 20 20l44 0 0 44c0 11 9 20 20 20s20-9 20-20l0-44 44 0c11 0 20-9 20-20s-9-20-20-20l-44 0 0-44c0-11-9-20-20-20s-20 9-20 20l0 44-44 0c-11 0-20 9-20 20z" />
                                                    </svg> Thêm
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="autosensor_shop_products_content_list_empty">
                            <p>Không có sản phẩm nào phù hợp với bộ lọc của bạn.</p>
                            <p>Hãy thử lọc sản phẩm khác hoặc thử tìm kiếm sản phẩm tương tự.</p>
                            <a href="{{ route('client.shop.index') }}" class="autosensor_shop_products_content_list_empty_button">
                                Xóa bộ lọc
                            </a>
                        </div>
                    @endif

                    @if (!empty($productsMain) && $productsMain->count() > 0)
                        <div class="autosensor_shop_products_content_pagination">
                            {{ $productsMain->links('pagination.custom') }}
                        </div>
                    @endif

                    {{-- Mô tả chi tiết danh mục + Sản phẩm gợi ý 7/3 --}}
                    @php
                        // Lấy 6 sản phẩm bất kỳ từ trang hiện tại
                        $suggestedProducts = $productsMain->isNotEmpty()
                            ? $productsMain->shuffle()->take(6)
                            : collect();
                    @endphp

                    <div class="autosensor_shop_desc_layout">

                        {{-- CỘT TRÁI: Mô tả danh mục (7) --}}
                        <div class="autosensor_shop_desc_layout_main">
                            @if (!empty($category) && !empty($category->description) && !($isCategoryBrandPage ?? false))
                                <div class="autosensor_shop_category_description">
                                    <div class="autosensor_shop_category_description_content">
                                        <!-- <h3 class="autosensor_shop_category_description_title">{{ $category->name }}</h3> -->
                                        <div class="autosensor_shop_category_description_text js-desc-text">
                                            {!! $category->description !!}
                                        </div>
                                        <button type="button" class="autosensor_shop_category_description_toggle js-desc-toggle" aria-expanded="false" style="display:none;">
                                            <span class="js-desc-label">Xem thêm</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                        </button>
                                    </div>
                                </div>
                            @else
                                <div class="autosensor_shop_category_description">
                                    <div class="autosensor_shop_category_description_content">
                                        <h3 class="autosensor_shop_category_description_title">{{ $pageTitle ?? $category->name }}</h3>
                                        <div class="autosensor_shop_category_description_text js-desc-text">
                                            {!! $pageDescription ?? '' !!}
                                        </div>
                                        <button type="button" class="autosensor_shop_category_description_toggle js-desc-toggle" aria-expanded="false" style="display:none;">
                                            <span class="js-desc-label">Xem thêm</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- CỘT PHẢI: Sản phẩm bạn có thể thích (3) --}}
                        @if ($suggestedProducts->isNotEmpty())
                        <aside class="autosensor_shop_desc_layout_sidebar">
                            <div class="autosensor_shop_suggested">
                                <h4 class="autosensor_shop_suggested_title">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                                    Sản phẩm bạn có thể thích
                                </h4>
                                <div class="autosensor_shop_suggested_list">
                                    @foreach ($suggestedProducts as $sp)
                                        <a href="{{ route('client.product.detail', ['slug' => $sp->slug]) }}" class="autosensor_shop_suggested_item">
                                            <div class="autosensor_shop_suggested_item_img">
                                                <img src="{{ asset('clients/assets/img/clothes/' . ($sp?->primaryImage?->url ?? 'no-image.webp')) }}"
                                                     alt="{{ $sp?->primaryImage?->alt ?? $sp?->name }}"
                                                     loading="lazy">
                                            </div>
                                            <div class="autosensor_shop_suggested_item_info">
                                                <p class="autosensor_shop_suggested_item_name">{{ $sp->name }}</p>
                                                <span class="autosensor_shop_suggested_item_price">
                                                    @if ($sp->sale_price && $sp->sale_price < $sp->price)
                                                        {{ number_format($sp->sale_price, 0, ',', '.') }}đ
                                                    @else
                                                        {{ number_format($sp->price ?? 0, 0, ',', '.') }}đ
                                                    @endif
                                                </span>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </aside>
                        @endif

                    </div>{{-- end autosensor_shop_desc_layout --}}
                </div>
            </div>
        </section>
    </main>

    @include('clients.templates.call')

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

@endsection
