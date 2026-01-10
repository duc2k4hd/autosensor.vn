<div class="autosensor_product_new">
    <h3 class="autosensor_single_desc_tabs_describe_product_new_title">✨ Sản phẩm mới</h3>
    <div style="display: flex; align-items: center; justify-content: center; margin: 1rem 0;">
        <hr style="flex: 1; height: 2px; background-color: #e6525e; border: none; margin: 0;">
        <span style="padding: 0 12px; color: #f74a4a; font-weight: bold;">Sản phẩm mới</span>
        <hr style="flex: 1; height: 2px; background-color: #e6525e; border: none; margin: 0;">
    </div>
    <div class="autosensor_single_desc_tabs_describe_product_new_grid">
        @if ($productNew)
            @foreach ($productNew as $new)
                <!-- Item -->
                <div class="autosensor_single_desc_tabs_describe_product_new_item">
                    <div class="autosensor_single_desc_tabs_describe_product_new_img">
                        <a href="/{{ $new->slug ?? 'san-pham-moi' }}">
                            <img loading="lazy" decoding="async" src="{{ asset('clients/assets/img/clothes/resize/230x230/' . ($new->primaryImage->url ?? 'no-image.webp')) }}"
                                srcset="
                                    {{ asset('clients/assets/img/clothes/resize/300x300/' . ($new->primaryImage->url ?? 'no-image.webp')) }} 1050w,
                                    {{ asset('clients/assets/img/clothes/resize/300x300/' . ($new->primaryImage->url ?? 'no-image.webp')) }} 155w
                                "
                                sizes="(max-width: 1050px) 155px, 230px"
                                onerror="this.onerror=null;this.src='{{ asset('clients/assets/img/clothes/no-image.webp') }}';this.removeAttribute('srcset');this.removeAttribute('sizes');"
                                alt="{{ $new->name }}">
                            <span class="autosensor_single_desc_tabs_describe_product_new_badge">New</span>
                        </a>
                    </div>
                    <div class="autosensor_single_desc_tabs_describe_product_new_info">
                        <h4 class="autosensor_single_desc_tabs_describe_product_new_name">
                            <a href="/{{ $new->slug ?? 'san-pham-moi' }}">{{ $new->name }}</a>
                        </h4>
                        <p class="autosensor_single_desc_tabs_describe_product_new_price">
                            {{ number_format($new->sale_price ?? $new->price, 0, ',', '.') }}đ</p>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>
