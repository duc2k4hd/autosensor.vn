@if(empty($faqs))
    <div class="product-show-empty">Sản phẩm này chưa có FAQ nào.</div>
@else
    <div style="display:grid;gap:14px;">
        @foreach($faqs as $faq)
            <article style="padding:18px;border:1px solid #e2e8f0;border-radius:14px;background:#fff;">
                <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;">
                    <h4 style="margin:0;color:#0f172a;font-size:16px;">{{ $faq['question'] }}</h4>
                    <span style="padding:4px 10px;border-radius:999px;background:#f8fafc;color:#475569;font-size:12px;font-weight:700;">#{{ $faq['order'] }}</span>
                </div>
                <div style="margin-top:12px;color:#334155;line-height:1.7;">
                    {!! nl2br(e($faq['answer'] ?: 'Chưa có câu trả lời.')) !!}
                </div>
            </article>
        @endforeach
    </div>
@endif
