@if(empty($images))
    <div class="product-show-empty">Sản phẩm này chưa có ảnh nào.</div>
@else
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;">
        @foreach($images as $image)
            <article style="border:1px solid #e2e8f0;border-radius:16px;background:#fff;overflow:hidden;">
                <div style="padding:12px;background:#f8fafc;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;gap:10px;align-items:center;">
                    <strong style="font-size:14px;color:#0f172a;">{{ $image['title'] ?: ($image['filename'] ?: 'Ảnh sản phẩm') }}</strong>
                    @if($image['is_primary'])
                        <span style="padding:4px 10px;border-radius:999px;background:#dcfce7;color:#166534;font-size:12px;font-weight:700;">Ảnh chính</span>
                    @endif
                </div>
                <div style="padding:14px;">
                    <div style="height:180px;display:flex;align-items:center;justify-content:center;background:#f8fafc;border-radius:12px;overflow:hidden;">
                        <img src="{{ $image['url'] }}" alt="{{ $image['alt'] }}" style="max-width:100%;max-height:180px;object-fit:contain;">
                    </div>
                    <div style="margin-top:12px;font-size:13px;color:#475569;display:grid;gap:6px;">
                        <div><strong>Alt:</strong> {{ $image['alt'] ?: 'Chưa có' }}</div>
                        <div><strong>File:</strong> {{ $image['filename'] ?: 'Không có' }}</div>
                        <div><strong>Thứ tự:</strong> {{ $image['order'] }}</div>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
@endif
