<div class="autosensor_product_featured">
    <h3 class="autosensor_single_desc_tabs_describe_product_new_title">⭐ Sản phẩm nổi bật</h3>
    <div style="display: flex; align-items: center; justify-content: center; margin: 1rem 0;">
        <hr style="flex: 1; height: 2px; background-color: #e6525e; border: none; margin: 0;">
        <span style="padding: 0 12px; color: #f74a4a; font-weight: bold;">Sản phẩm nổi bật</span>
        <hr style="flex: 1; height: 2px; background-color: #e6525e; border: none; margin: 0;">
    </div>
    <div class="autosensor_single_desc_tabs_describe_product_new_grid">
        @if ($productFeatured && $productFeatured->isNotEmpty())
            @foreach ($productFeatured->take(10) as $featured)
                <!-- Item -->
                <div class="autosensor_single_desc_tabs_describe_product_new_item">
                    <div class="autosensor_single_desc_tabs_describe_product_new_img">
                        <a href="/{{ $featured->slug ?? 'san-pham-noi-bat' }}">
                            <img loading="lazy" decoding="async" src="{{ asset('clients/assets/img/clothes/resize/230x230/' . ($featured->primaryImage->url ?? 'no-image.webp')) }}"
                                srcset="
                                    {{ asset('clients/assets/img/clothes/resize/300x300/' . ($featured->primaryImage->url ?? 'no-image.webp')) }} 1050w,
                                    {{ asset('clients/assets/img/clothes/resize/300x300/' . ($featured->primaryImage->url ?? 'no-image.webp')) }} 155w
                                "
                                sizes="(max-width: 1050px) 155px, 230px"
                                onerror="this.onerror=null;this.src='{{ asset('clients/assets/img/clothes/no-image.webp') }}';this.removeAttribute('srcset');this.removeAttribute('sizes');"
                                alt="{{ $featured->name }}">
                            @if($featured->isInFlashSale())
                                <span class="autosensor_single_desc_tabs_describe_product_new_badge" style="background: #ff4444;">⚡ Sale</span>
                            @elseif($featured->sale_price && $featured->sale_price < $featured->price)
                                <span class="autosensor_single_desc_tabs_describe_product_new_badge" style="background: #ff6b35;">-{{ round((($featured->price - $featured->sale_price) / $featured->price) * 100) }}%</span>
                            @else
                                <span class="autosensor_single_desc_tabs_describe_product_new_badge" style="background: #ffd700;">⭐ Hot</span>
                            @endif
                        </a>
                    </div>
                    <div class="autosensor_single_desc_tabs_describe_product_new_info">
                        <h4 class="autosensor_single_desc_tabs_describe_product_new_name">
                            <a href="/{{ $featured->slug ?? 'san-pham-noi-bat' }}">{{ $featured->name }}</a>
                        </h4>
                        <p class="autosensor_single_desc_tabs_describe_product_new_price">
                            {{ number_format($featured->sale_price ?? $featured->price, 0, ',', '.') }}đ</p>
                    </div>
                </div>
            @endforeach
        @else
            <p style="text-align: center; color: #999; padding: 20px; width: 100%;">Chưa có sản phẩm nổi bật.</p>
        @endif
    </div>
</div>
