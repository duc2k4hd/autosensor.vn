<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; }
        .header { background: #00468c; color: #fff; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { padding: 20px; background: #f9f9f9; }
        .field { margin-bottom: 15px; }
        .label { font-weight: bold; color: #00468c; }
        .footer { font-size: 12px; color: #777; text-align: center; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px; }
        .product-info { background: #fff; padding: 15px; border-left: 4px solid #00468c; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Yêu cầu Tư vấn Nhanh</h2>
    </div>
    <div class="content">
        <p>Xin chào Ban quản trị <strong>AutoSensor Việt Nam</strong>,</p>
        <p>Bạn vừa nhận được một yêu cầu tư vấn mới từ khách hàng:</p>

        <div class="field">
            <span class="label">Họ tên:</span> {{ $lead->name ?? 'Không cung cấp' }}
        </div>
        <div class="field">
            <span class="label">Số điện thoại:</span> {{ $lead->phone }}
        </div>
        <div class="field">
            <span class="label">Email:</span> {{ $lead->email ?? 'Không cung cấp' }}
        </div>
        <div class="field">
            <span class="label">Lời nhắn:</span><br>
            {{ $lead->message ?? 'Không có lời nhắn' }}
        </div>

        <div class="product-info">
            <p class="label" style="margin-top: 0;">Sản phẩm quan tâm:</p>
            <div><strong>Tên:</strong> {{ $lead->behavior_data['product_name'] ?? 'N/A' }}</div>
            <div><strong>SKU:</strong> {{ $lead->behavior_data['product_sku'] ?? 'N/A' }}</div>
            <div><strong>Loại Trigger:</strong> {{ $lead->trigger_type }}</div>
        </div>
    </div>
    <div class="footer">
        <p>Đây là email tự động từ hệ thống AutoSensor Việt Nam.</p>
        <p>&copy; {{ date('Y') }} AutoSensor Việt Nam. All rights reserved.</p>
    </div>
</body>
</html>
