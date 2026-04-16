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
                @foreach ($breadcrumbs as $breadcrumb)
                    @if ($loop->last)
                        <span class="breadcrumb-current">{{ $breadcrumb['name'] }}</span>
                    @else
                        <a href="{{ $breadcrumb['url'] }}">{{ $breadcrumb['name'] }}</a>
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
                                @if (!empty($brandFilters))
                                    <div class="autosensor_shop_products_filter_brands_list">
                                        @foreach ($brandFilters as $brandItem)
                                            <a href="{{ $brandItem['link'] }}" 
                                               class="autosensor_shop_products_filter_brands_item {{ $brandItem['is_selected'] ? 'autosensor_shop_products_filter_brands_item_active' : '' }}"
                                               style="display: flex; justify-content: space-between; align-items: center; text-decoration: none; color: inherit; margin-bottom: 8px; font-size: 14px;">
                                                <span class="autosensor_shop_products_filter_brands_item_name">{{ $brandItem['name'] }}</span>
                                                <span class="autosensor_shop_products_filter_brands_item_count" style="color: #888; font-size: 12px;">({{ $brandItem['products_count'] }})</span>
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
                                        @if (!empty($selectedTagSlugs))
                                            @foreach ($selectedTagSlugs as $tagSlug)
                                                <input type="hidden" name="tags[]" value="{{ $tagSlug }}">
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
                            @foreach ($categoryFilters as $cat)
                                <div
                                    class="autosensor_shop_products_filter_categories_content_category {{ $cat['is_active'] ? 'autosensor_shop_products_filter_categories_content_category_active' : '' }}">
                                    <div class="autosensor_shop_products_filter_categories_content_category_image">
                                        <a href="{{ $cat['link'] }}">
                                            <img width="30px" height="30px"
                                                class="autosensor_shop_products_filter_categories_content_category_image_img"
                                                src="{{ $cat['image_url'] }}"
                                                alt="{{ $cat['name'] }}">
                                        </a>
                                    </div>
                                    <div class="autosensor_shop_products_filter_categories_content_category_text">
                                        <a href="{{ $cat['link'] }}">
                                            <p>{{ $cat['name'] }}</p>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
            
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
                                        <a href="{{ $product->shop_card['url'] }}">
                                            <img class="autosensor_shop_products_filter_new_products_item_image_img"
                                                src="{{ $product->shop_card['image_url'] }}"
                                                alt="{{ $product->shop_card['image_alt'] }}"
                                                title="{{ $product->shop_card['image_title'] }}">
                                        </a>
                                    </div>
                                    <div class="autosensor_shop_products_filter_new_products_item_info">
                                        <a href="{{ $product->shop_card['url'] }}">
                                            <h4 class="autosensor_shop_products_filter_new_products_item_info_title">
                                                {{ $product->name }}</h4>
                                        </a>
                                        <p class="autosensor_shop_products_filter_new_products_item_info_price">
                                            {{ $product->shop_card['display_price_formatted'] }}</p>
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
                                    @if (!empty($selectedTagSlugs))
                                        @foreach ($selectedTagSlugs as $tagSlug)
                                            <input type="hidden" name="tags[]" value="{{ $tagSlug }}">
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
                                    @if (!empty($selectedTagSlugs))
                                        @foreach ($selectedTagSlugs as $tagSlug)
                                            <input type="hidden" name="tags[]" value="{{ $tagSlug }}">
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
                                <div class="autosensor_shop_products_content_list_item">
                                    <div class="autosensor_shop_products_content_list_item_label">
                                        {{ $product->shop_card['label'] }}
                                    </div>
                                    <div class="autosensor_shop_products_content_list_item_image">
                                        <a href="{{ $product->shop_card['url'] }}">
                                            <img class="autosensor_shop_products_content_list_item_image_img"
                                                src="{{ $product->shop_card['image_url'] }}"
                                                alt="{{ $product->shop_card['image_alt'] }}"
                                                title="{{ $product->shop_card['image_title'] }}">
                                        </a>
                                    </div>
                                    <div class="autosensor_shop_products_content_list_item_category">
                                        <h5 class="autosensor_shop_products_content_list_item_category_name">
                                            {{ $product->shop_card['brand_name'] }}
                                        </h5>
                                    </div>
                                    <div class="autosensor_shop_products_content_list_item_title">
                                        <a href="{{ $product->shop_card['url'] }}">
                                            <h4 class="autosensor_shop_products_content_list_item_title_name">
                                                {{ $product->name }}
                                            </h4>
                                        </a>
                                    </div>
                                    <div class="autosensor_shop_products_content_list_item_star">
                                        <span class="autosensor_shop_products_content_list_item_star_icon">
                                            {{ $product->shop_card['rating_text'] }}
                                        </span>
                                        <span class="autosensor_shop_products_content_list_item_star_count">
                                            ({{ number_format($product->shop_card['review_count']) }} review)
                                        </span>
                                    </div>
                                    <div class="autosensor_shop_products_content_list_item_price">
                                        @if ($product->shop_card['has_sale'])
                                            <span class="autosensor_shop_products_content_list_item_price_new">
                                                {{ $product->shop_card['display_price_formatted'] }}
                                            </span>
                                            <span class="autosensor_shop_products_content_list_item_price_old">
                                                {{ $product->shop_card['original_price_formatted'] }}
                                            </span>
                                        @else
                                            <span class="autosensor_shop_products_content_list_item_price_new">
                                                {{ $product->shop_card['display_price_formatted'] }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="autosensor_shop_products_content_list_item_addtocart">
                                        @if($product->shop_card['has_variants'])
                                            <button type="button" 
                                                    class="autosensor_shop_products_content_list_item_addtocart_button open-variant-modal-btn" 
                                                    style="width: 100%;" 
                                                    data-product-id="{{ $product->id }}"
                                                    data-product-name="{{ $product->name }}"
                                                    data-product-slug="{{ $product->slug }}"
                                                    data-product-image="{{ $product->shop_card['image_url'] }}"
                                                    data-product-price="{{ $product->price }}"
                                                    data-product-sale-price="{{ $product->sale_price ?? '' }}"
                                                    data-variants='{{ $product->shop_card['variant_payload_json'] }}'>
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
                                        <a href="{{ $sp->shop_card['url'] }}" class="autosensor_shop_suggested_item">
                                            <div class="autosensor_shop_suggested_item_img">
                                                <img src="{{ $sp->shop_card['image_url'] }}"
                                                     alt="{{ $sp->shop_card['image_alt'] }}"
                                                     loading="lazy">
                                            </div>
                                            <div class="autosensor_shop_suggested_item_info">
                                                <p class="autosensor_shop_suggested_item_name">{{ $sp->name }}</p>
                                                <span class="autosensor_shop_suggested_item_price">
                                                    {{ $sp->shop_card['display_price_formatted'] }}
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
