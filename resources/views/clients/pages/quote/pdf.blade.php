@php
    /** @var \App\Models\Quote $quote */
    $siteName = $settings->site_name ?? 'AutoSensor Việt Nam';
    $siteAddress = $settings->business_address ?? '';
    $sitePhone = $settings->business_phone ?? ($settings->contact_phone ?? '');
    $siteEmail = $settings->contact_email ?? '';
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Báo giá #{{ $quote->id }} - {{ $siteName }}</title>
    <style>
        * {
            font-family: DejaVu Sans, sans-serif;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111827;
        }
        h1, h2, h3 {
            font-family: DejaVu Sans, sans-serif;
            color: #2563EB;
            margin: 0 0 6px 0;
        }
        .header, .footer {
            width: 100%;
            margin-bottom: 10px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: top;
            font-family: DejaVu Sans, sans-serif;
        }
        .meta {
            margin-top: 6px;
            font-size: 11px;
            font-family: DejaVu Sans, sans-serif;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            font-family: DejaVu Sans, sans-serif;
        }
        table.items th,
        table.items td {
            border: 1px solid #E5E7EB;
            padding: 6px;
            text-align: left;
            font-family: DejaVu Sans, sans-serif;
        }
        table.items th {
            background: #EFF6FF;
            font-weight: 600;
            font-family: DejaVu Sans, sans-serif;
        }
        .text-right { 
            text-align: right; 
        }
        .total-row {
            font-weight: 700;
            background: #F9FAFB;
            font-family: DejaVu Sans, sans-serif;
        }
        .note {
            margin-top: 10px;
            font-size: 11px;
            font-family: DejaVu Sans, sans-serif;
        }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td>
                    <h1>BÁO GIÁ</h1>
                    <div class="meta">
                        <div><strong>Mã báo giá:</strong> #{{ $quote->id }}</div>
                        <div><strong>Ngày:</strong> {{ $quote->created_at?->format('d/m/Y H:i') }}</div>
                    </div>
                </td>
                <td style="text-align: right;">
                    <h3>{{ $siteName }}</h3>
                    @if($siteAddress)
                        <div>{{ $siteAddress }}</div>
                    @endif
                    @if($sitePhone)
                        <div>Điện thoại: {{ $sitePhone }}</div>
                    @endif
                    @if($siteEmail)
                        <div>Email: {{ $siteEmail }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <h3>Thông tin khách hàng</h3>
    <table class="header-table">
        <tr>
            <td>
                <div><strong>Khách hàng:</strong> {{ $quote->name }}</div>
                @if($quote->phone)
                    <div><strong>Điện thoại:</strong> {{ $quote->phone }}</div>
                @endif
                @if($quote->email)
                    <div><strong>Email:</strong> {{ $quote->email }}</div>
                @endif
            </td>
        </tr>
    </table>

    <h3>Chi tiết báo giá</h3>
    <table class="items">
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>SKU</th>
                <th class="text-right">Quantity</th>
                <th class="text-right">Price</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $row['product_name'] ?? '' }}</td>
                    <td>
                        {{ $row['sku'] ?? '' }}
                        @if(!empty($row['variant_name']))
                            - {{ $row['variant_name'] }}
                        @endif
                    </td>
                    <td class="text-right">{{ number_format((int) ($row['quantity'] ?? 0), 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format((float) ($row['unit_price'] ?? 0), 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format((float) ($row['line_total'] ?? 0), 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="5" class="text-right">Tổng cộng</td>
                <td class="text-right">{{ number_format((float) $quote->total_amount, 0, ',', '.') }} đ</td>
            </tr>
        </tbody>
    </table>

    @if($quote->note)
        <div class="note">
            <strong>Ghi chú từ khách hàng:</strong><br>
            {{ $quote->note }}
        </div>
    @endif

    <div class="note">
        <em>
            Báo giá này chưa bao gồm phí vận chuyển, thuế VAT (nếu có) và có thể thay đổi tùy theo thời điểm đặt hàng.
            Vui lòng liên hệ lại AutoSensor để được tư vấn và chốt đơn hàng chính thức.
        </em>
    </div>
</body>
</html>

