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
        .reply-content { background: #f8fafc; border-left: 4px solid #00468c; padding: 20px; margin: 20px 0; font-style: italic; }
        .info-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 15px; margin-bottom: 20px; font-size: 14px; }
        .label { font-weight: bold; color: #64748b; width: 100px; display: inline-block; }
        .cta-section { text-align: center; margin: 25px 0; }
        .btn { background: #00468c; color: #ffffff !important; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block; }
        .footer { background: #f8fafc; padding: 20px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0; }
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
            <p>Chúng tôi đã nhận được yêu cầu tư vấn của bạn về sản phẩm <strong>{{ $lead->product->name ?? ($lead->behavior_data['product_name'] ?? 'tại website') }}</strong>.</p>
            
            <p>Dưới đây là thông tin phản hồi từ đội ngũ hỗ trợ:</p>

            <div class="reply-content">
                {!! $replyContent !!}
            </div>

            <p>Nếu bạn có thêm bất kỳ thắc mắc nào, đừng ngần ngại phản hồi lại email này hoặc gọi trực tiếp cho chúng tôi qua số hotline.</p>

            <div class="info-box">
                <strong>Thông tin yêu cầu của bạn:</strong><br>
                <span class="label">Sản phẩm:</span> {{ $lead->product->name ?? 'N/A' }}<br>
                <span class="label">Ngày gửi:</span> {{ $lead->created_at->format('d/m/Y') }}
            </div>

            <div class="cta-section">
                <a href="{{ route('client.home.index') }}" class="btn">Ghé thăm Website</a>
            </div>
        </div>
        <div class="footer">
            <p><strong>{{ $settings['site_name'] ?? 'AutoSensor Việt Nam' }}</strong> - Giải pháp cảm biến toàn diện.</p>
            <p>Địa chỉ: {{ $settings['contact_address'] ?? 'Hải Phòng' }}</p>
            <p>Hotline: {{ $settings['contact_phone'] ?? '' }}</p>
            <p>&copy; {{ date('Y') }} {{ $settings['site_name'] ?? 'AutoSensor Việt Nam' }}.</p>
        </div>
    </div>
</body>
</html>
