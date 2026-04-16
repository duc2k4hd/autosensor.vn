@if(empty($variants))
    <div class="product-show-empty">Sản phẩm này chưa có biến thể nào.</div>
@else
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="padding:12px 14px;border-bottom:1px solid #e2e8f0;text-align:left;">Tên</th>
                    <th style="padding:12px 14px;border-bottom:1px solid #e2e8f0;text-align:left;">SKU</th>
                    <th style="padding:12px 14px;border-bottom:1px solid #e2e8f0;text-align:right;">Giá</th>
                    <th style="padding:12px 14px;border-bottom:1px solid #e2e8f0;text-align:right;">Giá KM</th>
                    <th style="padding:12px 14px;border-bottom:1px solid #e2e8f0;text-align:right;">Tồn kho</th>
                    <th style="padding:12px 14px;border-bottom:1px solid #e2e8f0;text-align:center;">Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                @foreach($variants as $variant)
                    <tr>
                        <td style="padding:12px 14px;border-bottom:1px solid #e2e8f0;vertical-align:top;">
                            <strong style="color:#0f172a;">{{ $variant['name'] }}</strong>
                            @if(!empty($variant['note']))
                                <div style="margin-top:6px;color:#64748b;font-size:13px;">{{ $variant['note'] }}</div>
                            @endif
                        </td>
                        <td style="padding:12px 14px;border-bottom:1px solid #e2e8f0;">{{ $variant['sku'] ?: '-' }}</td>
                        <td style="padding:12px 14px;border-bottom:1px solid #e2e8f0;text-align:right;">{{ number_format((float) $variant['price'], 0, ',', '.') }}₫</td>
                        <td style="padding:12px 14px;border-bottom:1px solid #e2e8f0;text-align:right;">
                            {{ $variant['sale_price'] ? number_format((float) $variant['sale_price'], 0, ',', '.') . '₫' : '-' }}
                        </td>
                        <td style="padding:12px 14px;border-bottom:1px solid #e2e8f0;text-align:right;">
                            {{ is_null($variant['stock_quantity']) ? 'Không giới hạn' : $variant['stock_quantity'] }}
                        </td>
                        <td style="padding:12px 14px;border-bottom:1px solid #e2e8f0;text-align:center;">
                            <span style="padding:4px 10px;border-radius:999px;background:{{ $variant['is_active'] ? '#dcfce7' : '#fee2e2' }};color:{{ $variant['is_active'] ? '#166534' : '#991b1b' }};font-size:12px;font-weight:700;">
                                {{ $variant['is_active'] ? 'Kích hoạt' : 'Tắt' }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
