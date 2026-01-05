<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác thực email</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #0f5132 0%, #198754 50%, #20c997 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: #fff; margin: 0; font-size: 28px;">Xác thực email</h1>
    </div>
    
    <div style="background: #fff; padding: 30px; border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 10px 10px;">
        <p style="font-size: 16px; margin-bottom: 20px;">Xin chào <strong>{{ $account?->name ?? $account?->email ?? 'Bạn' }}</strong>,</p>
        
        <p style="font-size: 16px; margin-bottom: 20px;">
            Cảm ơn bạn đã đăng ký tài khoản tại <strong>{{ config('site.short_name') ?? 'AutoSensor Việt Nam' }}</strong>.
        </p>
        
        <p style="font-size: 16px; margin-bottom: 20px;">
            Để hoàn tất đăng ký, vui lòng xác thực email của bạn bằng cách nhấp vào nút bên dưới:
        </p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $verificationUrl }}" 
               style="display: inline-block; background: linear-gradient(135deg, #198754 0%, #0f5132 100%); color: #fff; padding: 15px 40px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px;">
                Xác thực email
            </a>
        </div>
        
        <p style="font-size: 14px; color: #666; margin-top: 30px;">
            Hoặc copy và dán link sau vào trình duyệt:
        </p>
        <p style="font-size: 12px; color: #198754; word-break: break-all; background: #f5f5f5; padding: 10px; border-radius: 5px;">
            {{ $verificationUrl }}
        </p>
        
        <p style="font-size: 14px; color: #666; margin-top: 30px;">
            Link này sẽ hết hạn sau 24 giờ.
        </p>
        
        <p style="font-size: 14px; color: #666; margin-top: 20px;">
            Nếu bạn không đăng ký tài khoản này, vui lòng bỏ qua email này.
        </p>
        
        <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 30px 0;">
        
        <p style="font-size: 12px; color: #999; text-align: center; margin: 0;">
            © {{ date('Y') }} {{ config('site.short_name') ?? 'AutoSensor Việt Nam' }}. Tất cả quyền được bảo lưu.
        </p>
    </div>
</body>
</html>

