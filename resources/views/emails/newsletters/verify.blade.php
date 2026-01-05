<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Xác nhận đăng ký nhận bản tin</title>
</head>
<body style="font-family: -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif; background-color:#f3f4f6; padding:24px;">
<table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;">
    <tr>
        <td style="padding:24px 24px 16px 24px; background:linear-gradient(135deg,#6366f1,#8b5cf6); color:#ffffff;">
            <h1 style="margin:0;font-size:22px;">Xác nhận đăng ký nhận bản tin</h1>
        </td>
    </tr>
    <tr>
        <td style="padding:24px;">
            <p style="margin:0 0 16px 0;">Xin chào <strong>{{ $subscription->email }}</strong>,</p>
            <p style="margin:0 0 16px 0;">
                Cảm ơn bạn đã đăng ký nhận bản tin từ <strong>{{ config('app.name') }}</strong>.
                Vui lòng nhấn vào nút bên dưới để xác nhận đăng ký.
            </p>
            <p style="text-align:center;margin:24px 0;">
                <a href="{{ $verifyUrl }}" style="display:inline-block;padding:12px 24px;background:#6366f1;color:#ffffff;text-decoration:none;border-radius:999px;font-weight:600;">
                    Xác nhận đăng ký
                </a>
            </p>
            <p style="margin:0 0 16px 0;font-size:13px;color:#6b7280;">
                Nếu bạn không thực hiện đăng ký này, vui lòng bỏ qua email.
            </p>
            <p style="margin:24px 0 0 0;">
                Trân trọng,<br>
                <strong>{{ config('app.name') }}</strong>
            </p>
        </td>
    </tr>
</table>
</body>
</html>


