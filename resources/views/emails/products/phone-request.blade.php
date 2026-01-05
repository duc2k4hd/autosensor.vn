<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Khách hàng cần tư vấn</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            text-align: center
        }

        .content {
            background: #f9fafb;
            padding: 30px;
            border: 1px solid #e5e7eb;
            border-top: none
        }

        .info-box {
            background: #fff;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px
        }

        .info-label {
            font-weight: 600;
            color: #667eea;
            display: inline-block;
            min-width: 120px
        }

        .info-value {
            color: #111827
        }

        .phone-highlight {
            font-size: 24px;
            font-weight: 700;
            color: #059669;
            text-decoration: none;
            display: block;
            margin: 10px 0
        }

        .button {
            display: inline-block;
            background: #667eea;
            color: #fff;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
            font-weight: 600
        }

        .footer {
            text-align: center;
            padding: 20px;
            color: #6b7280;
            font-size: 12px;
            border-top: 1px solid #e5e7eb
        }

        .meta-info {
            font-size: 12px;
            color: #6b7280;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb
        }

    </style>
</head>
<body>
    <div class="header">
        <h1>🔔 Khách hàng cần tư vấn</h1>
        <p style="margin:5px 0 0;">Yêu cầu gọi lại từ trang sản phẩm</p>
    </div>
    <div class="content">
        <div class="info-box">
            <div><span class="info-label">📞 Số điện thoại:</span><a href="tel:{{ $phone }}" class="phone-highlight">{{ $phone }}</a></div>
        </div>
        <div class="info-box">
            <div style="margin-bottom:10px;"><span class="info-label">🛍️ Sản phẩm:</span><span class="info-value">{{ $productName }}</span></div>
            <div style="margin-bottom:10px;"><span class="info-label">📦 Mã SKU:</span><span class="info-value">{{ $productSku }}</span></div>
            <div><a href="{{ $productUrl }}" class="button" target="_blank">Xem sản phẩm</a></div>
        </div>
        <div class="meta-info">
            <p><strong>Thông tin kỹ thuật:</strong></p>
            <p>IP Address: {{ $ipAddress }}</p>
            <p>User Agent: {{ Str::limit($userAgent,100) }}</p>
            <p>Thời gian: {{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>
    <div class="footer">
        <p>Email này được gửi tự động từ hệ thống AutoSensor Việt Nam</p>
        <p>Vui lòng gọi lại cho khách hàng trong thời gian sớm nhất.</p>
    </div>
</body>
</html>
