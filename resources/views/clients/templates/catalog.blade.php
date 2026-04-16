<div class="autosensor_catalog_wrapper">
    @if(empty($catalogLinks))
        <div class="autosensor_catalog_empty">
            <p>Chưa có catalog cho sản phẩm này.</p>
            <p>Liên hệ CSKH để nhận tài liệu kỹ thuật mới nhất.</p>
            <a class="catalog_contact" href="https://zalo.me/{{ $settings->contact_zalo ?? '0827786198' }}" target="_blank">Chat Zalo</a>
        </div>
    @else
        <div class="autosensor_catalog_grid">
            @foreach($catalogLinks as $catalog)
                <div class="autosensor_catalog_card">
                    <div class="catalog_icon">📄</div>
                    <div class="catalog_body">
                        <div class="catalog_title">{{ $catalog['label'] ?? 'Catalog' }}</div>
                        <div class="catalog_desc">Tài liệu kỹ thuật / HDSD / Datasheet</div>
                        <div class="catalog_actions">
                            <a class="catalog_btn view" href="{{ $catalog['url'] ?? '#' }}" target="_blank" rel="noopener">Xem</a>
                            <a class="catalog_btn download" href="{{ $catalog['url'] ?? '#' }}" download>⬇ Tải</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
