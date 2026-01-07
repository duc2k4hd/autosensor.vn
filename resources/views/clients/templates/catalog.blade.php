@php
    $catalogs = $product->link_catalog ?? [];
    if (!is_array($catalogs)) {
        $catalogs = [];
    }
    $catalogs = array_values(array_filter($catalogs));
@endphp

<div class="autosensor_catalog_wrapper">
    @if(empty($catalogs))
        <div class="autosensor_catalog_empty">
            <p>Chưa có catalog cho sản phẩm này.</p>
            <p>Liên hệ CSKH để nhận tài liệu kỹ thuật mới nhất.</p>
            <a class="catalog_contact" href="https://zalo.me/{{ $settings->contact_zalo ?? '0827786198' }}" target="_blank">Chat Zalo</a>
        </div>
    @else
        <div class="autosensor_catalog_grid">
            @foreach($catalogs as $idx => $catalog)
                @php
                    $fileName = basename($catalog);
                    $label = $fileName ?: 'Catalog ' . ($idx + 1);
                @endphp
                <div class="autosensor_catalog_card">
                    <div class="catalog_icon">📄</div>
                    <div class="catalog_body">
                        <div class="catalog_title">{{ $label }}</div>
                        <div class="catalog_desc">Tài liệu kỹ thuật / HDSD / Datasheet</div>
                        <div class="catalog_actions">
                            <a class="catalog_btn view" href="{{ asset($catalog) }}" target="_blank" rel="noopener">Xem</a>
                            <a class="catalog_btn download" href="{{ asset($catalog) }}" download>⬇ Tải</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>