@if(empty($howTos))
    <div class="product-show-empty">Sản phẩm này chưa có hướng dẫn nào.</div>
@else
    <div style="display:grid;gap:16px;">
        @foreach($howTos as $howTo)
            <article style="padding:18px;border:1px solid #e2e8f0;border-radius:14px;background:#fff;">
                <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;">
                    <div>
                        <h4 style="margin:0;color:#0f172a;font-size:16px;">{{ $howTo['title'] }}</h4>
                        @if(!empty($howTo['description']))
                            <div style="margin-top:8px;color:#475569;line-height:1.7;">{{ $howTo['description'] }}</div>
                        @endif
                    </div>
                    <span style="padding:4px 10px;border-radius:999px;background:{{ $howTo['is_active'] ? '#dcfce7' : '#fee2e2' }};color:{{ $howTo['is_active'] ? '#166534' : '#991b1b' }};font-size:12px;font-weight:700;">
                        {{ $howTo['is_active'] ? 'Hiển thị' : 'Ẩn' }}
                    </span>
                </div>

                <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;margin-top:16px;">
                    <div>
                        <strong style="display:block;margin-bottom:10px;color:#0f172a;">Các bước</strong>
                        @if(!empty($howTo['steps']))
                            <ol style="margin:0;padding-left:18px;color:#334155;line-height:1.8;">
                                @foreach($howTo['steps'] as $step)
                                    <li>{{ $step }}</li>
                                @endforeach
                            </ol>
                        @else
                            <div class="product-show-empty">Không có bước nào.</div>
                        @endif
                    </div>
                    <div>
                        <strong style="display:block;margin-bottom:10px;color:#0f172a;">Dụng cụ</strong>
                        @if(!empty($howTo['supplies']))
                            <ul style="margin:0;padding-left:18px;color:#334155;line-height:1.8;">
                                @foreach($howTo['supplies'] as $supply)
                                    <li>{{ $supply }}</li>
                                @endforeach
                            </ul>
                        @else
                            <div class="product-show-empty">Không có dụng cụ nào.</div>
                        @endif
                    </div>
                </div>
            </article>
        @endforeach
    </div>
@endif
