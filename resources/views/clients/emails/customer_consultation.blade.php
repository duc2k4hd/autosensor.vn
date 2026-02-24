<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f7f9; }
        .wrapper { max-width: 600px; margin: 20px auto; background: #ffffff; border: 1px solid #e1e8ed; border-radius: 8px; overflow: hidden; }
        .header { background: #00468c; color: #ffffff; padding: 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 22px; }
        .content { padding: 25px; }
        .greeting { font-size: 18px; font-weight: bold; color: #00468c; margin-bottom: 15px; }
        .info-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 15px; margin-bottom: 20px; }
        .info-item { margin-bottom: 8px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 5px; }
        .label { font-weight: bold; color: #64748b; width: 120px; display: inline-block; }
        .cta-section { text-align: center; margin: 25px 0; padding: 15px; background: #ebf5ff; border-radius: 8px; }
        .btn { background: #00468c; color: #ffffff !important; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block; }
        .footer { background: #f8fafc; padding: 20px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0; }
        .footer-link { color: #00468c; text-decoration: none; font-weight: bold; margin: 0 5px; }
    </style>
</head>
@php
    $settings = App\Models\Setting::pluck('value', 'key')->toArray();
@endphp
<body>
    <div class="wrapper">
        <div class="header">
            <h1>{{ $settings['site_name'] ?? 'AutoSensor Việt Nam' }}</h1>
        </div>
        <div class="content">
            <div class="greeting">Xin chào {{ $lead->name ?? 'Quý khách' }},</div>
            <p>Cảm ơn bạn đã gửi yêu cầu tư vấn nhanh. Đội ngũ kỹ thuật của <strong>{{ $settings['site_name'] ?? 'AutoSensor Việt Nam' }}</strong> đã nhận được thông tin và sẽ gọi lại cho bạn qua số điện thoại <strong>{{ $lead->phone }}</strong>.</p>       

            <div class="info-box">
                <div class="info-item"><span class="label">SĐT:</span> <span>{{ $lead->phone }}</span></div>
                @if($lead->email)
                <div class="info-item"><span class="label">Email:</span> <span>{{ $lead->email }}</span></div>
                @endif
                <div class="info-item">
                    <span class="label">Sản phẩm:</span>
                    <span>
                        @if($product)
                        <a href="{{ route('client.product.detail', $product->slug) }}" style="color: #00468c;">{{ $product->name }}</a>
                        @else
                        {{ $lead->behavior_data['product_name'] ?? 'Sản phẩm bạn đang quan tâm' }}
                        @endif
                    </span>
                </div>
            </div>

            <p>Bạn có thể tham khảo thêm các giải pháp tự động hóa tại website của chúng tôi.</p>

            <div class="cta-section">
                <a href="{{ route('client.shop.index') }}" class="btn">Xem Cửa Hàng</a>
            </div>
        </div>
        <div class="footer">
            <p>
                <a href="{{ route('client.home.index') }}" class="footer-link">Trang chủ</a> | 
                <a href="{{ route('client.blog.index') }}" class="footer-link">Blog kiến thức</a>
            </p>
            <p><strong>{{ $settings['site_name'] ?? 'AutoSensor Việt Nam' }}</strong> - Giải pháp cảm biến toàn diện.</p>
            <p>Địa chỉ: {{ $settings['contact_address'] ?? 'Hải Phòng' }}</p>
            <p>&copy; {{ date('Y') }} {{ $settings['site_name'] ?? 'AutoSensor Việt Nam' }}.</p>
        </div>
    </div>
</body>
</html>
