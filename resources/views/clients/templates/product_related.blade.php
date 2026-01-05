<div style="display: flex; align-items: center; justify-content: center; margin: 1rem 0;">
    <hr style="flex: 1; height: 2px; background-color: #e6525e; border: none; margin: 0;">
    <span style="padding: 0 12px; color: #f74a4a; font-weight: bold;">Sản phẩm liên quan</span>
    <hr style="flex: 1; height: 2px; background-color: #e6525e; border: none; margin: 0;">
</div>
<div class="xanhworld_single_product_related">
    <h3 class="xanhworld_single_product_related_title">❤️ Sản phẩm liên quan</h3>

    <div class="xanhworld_single_product_related_grid">
        @if($productRelated)
            @foreach ($productRelated as $related)
                <!-- Item -->
                <div class="xanhworld_single_product_related_item">
                    <a href="/san-pham/{{ $related->slug ?? 'san-pham-lien-quan' }}" class="xanhworld_single_product_related_img">
                        <img loading="lazy" decoding="async" src="{{ asset('clients/assets/img/clothes/resize/215x215/' . ($related->primaryImage->url ?? 'no-image.webp')) }}" 
                            srcset="
                                {{ asset('clients/assets/img/clothes/resize/300x300/' . ($related->primaryImage->url ?? 'no-image.webp')) }} 215w,
                                {{ asset('clients/assets/img/clothes/resize/300x300/' . ($related->primaryImage->url ?? 'no-image.webp')) }} 175w
                            "
                            sizes="(max-width: 1050px) 175px, 215px"
                            alt="{{ $related->name }}">
                        @if($related->is_featured)
                            <span class="xanhworld_single_product_related_badge">Hot</span>
                        @elseif($related->created_at->diffInDays(now()) <= 30)
                            <span class="xanhworld_single_product_related_badge">New</span>
                        @endif
                    </a>
                    <div class="xanhworld_single_product_related_info">
                        <a href="/san-pham/{{ $related->slug ?? 'san-pham-lien-quan' }}" class="xanhworld_single_product_related_name">{{ $related->name }}</a>
                        <p class="xanhworld_single_product_related_price">{{ number_format($related?->sale_price ?? $related?->price ?? 0, 0, ',', '.') }}đ</p>
                    </div>
                </div>
            @endforeach
        @else
            
        @endif
    </div>
</div>
