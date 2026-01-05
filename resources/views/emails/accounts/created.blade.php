<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin tài khoản mới</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #667eea;
        }
        .header h1 {
            color: #667eea;
            margin: 0;
            font-size: 24px;
        }
        .content {
            margin-bottom: 30px;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .credentials {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .credentials h3 {
            margin-top: 0;
            color: #856404;
        }
        .credential-item {
            margin: 10px 0;
            font-size: 16px;
        }
        .credential-label {
            font-weight: 600;
            color: #333;
            display: inline-block;
            width: 120px;
        }
        .credential-value {
            font-family: 'Courier New', monospace;
            background: #fff;
            padding: 5px 10px;
            border-radius: 4px;
            border: 1px solid #ddd;
            color: #d63384;
            font-weight: 600;
        }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .warning-box strong {
            color: #856404;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
            font-weight: 600;
        }
        .button:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
        .disclaimer {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 13px;
        }
        .disclaimer strong {
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Chào mừng đến với {{ $siteName }}!</h1>
        </div>

        <div class="content">
            <p>Xin chào <strong>{{ $accountName }}</strong>,</p>

            <p>Tài khoản của bạn đã được tạo thành công trên hệ thống <strong>{{ $siteName }}</strong>.</p>

            <div class="info-box">
                <p style="margin: 0;"><strong>📧 Email đăng nhập:</strong> {{ $accountEmail }}</p>
                <p style="margin: 10px 0 0 0;"><strong>👤 Vai trò:</strong> {{ ucfirst($accountRole) }}</p>
            </div>

            <div class="credentials">
                <h3>🔐 Thông tin đăng nhập</h3>
                <div class="credential-item">
                    <span class="credential-label">Email:</span>
                    <span class="credential-value">{{ $accountEmail }}</span>
                </div>
                <div class="credential-item">
                    <span class="credential-label">Mật khẩu:</span>
                    <span class="credential-value">{{ $password }}</span>
                </div>
            </div>

            <div class="warning-box">
                <strong>⚠️ CẢNH BÁO BẢO MẬT QUAN TRỌNG:</strong>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>Mật khẩu trên là mật khẩu tạm thời được tạo tự động</li>
                    <li><strong>Vui lòng đổi mật khẩu ngay sau khi đăng nhập lần đầu</strong></li>
                    <li>Không chia sẻ thông tin đăng nhập với bất kỳ ai</li>
                    <li>Nếu bạn không phải người tạo tài khoản này, vui lòng bỏ qua email này</li>
                </ul>
            </div>

            <div class="button-container">
                <a href="{{ $forgotPasswordUrl }}" class="button">🔑 Đổi mật khẩu ngay</a>
            </div>

            <p>Hoặc bạn có thể truy cập trang quên mật khẩu tại:</p>
            <p style="word-break: break-all; color: #667eea; background: #f8f9fa; padding: 10px; border-radius: 4px;">
                <a href="{{ $forgotPasswordUrl }}" style="color: #667eea;">{{ $forgotPasswordUrl }}</a>
            </p>

            <div class="disclaimer">
                <strong>📋 Từ chối trách nhiệm:</strong>
                <p style="margin: 10px 0 0 0;">
                    Chúng tôi <strong>KHÔNG CHỊU TRÁCH NHIỆM</strong> về bất kỳ thiệt hại nào phát sinh từ việc người dùng không đổi mật khẩu sau khi nhận được thông tin tài khoản. 
                    Người dùng có trách nhiệm bảo vệ thông tin đăng nhập của mình và đổi mật khẩu ngay lập tức để đảm bảo an toàn tài khoản.
                </p>
            </div>
        </div>

        <div class="footer">
            <p><strong>{{ $siteName }}</strong></p>
            <p>
                <a href="{{ $siteUrl }}">{{ $siteUrl }}</a>
            </p>
            <p>Email này được gửi tự động, vui lòng không trả lời email này.</p>
            <p>Nếu bạn gặp vấn đề, vui lòng liên hệ bộ phận hỗ trợ.</p>
            <p style="margin-top: 20px; color: #999;">
                &copy; {{ date('Y') }} {{ $siteName }}. Tất cả quyền được bảo lưu.
            </p>
        </div>
    </div>
</body>
</html>

