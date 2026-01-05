<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thông báo từ {{ config('app.name') }}</title>
</head>
<body style="font-family: -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif; background-color:#f3f4f6; padding:24px;">
<table width="100%" cellpadding="0" cellspacing="0" style="max-width:650px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;">
    <tr>
        <td style="padding:24px 24px 16px 24px; background:linear-gradient(135deg,#0ea5e9,#6366f1); color:#ffffff;">
            <h1 style="margin:0;font-size:22px;">Thông báo từ {{ config('app.name') }}</h1>
        </td>
    </tr>
    <tr>
        <td style="padding:24px;">
            <p style="margin:0 0 16px 0;">Xin chào <strong>{{ $subscription->email }}</strong>,</p>

            @if (!empty($content))
                <div style="margin:16px 0;">
                    {!! $content !!}
                </div>
            @else
                <p style="margin:0 0 16px 0;">
                    Chúng tôi có những thông tin và ưu đãi đặc biệt dành cho bạn!
                </p>
            @endif

            @if (!empty($cta_url) && !empty($cta_text))
                <p style="text-align:center;margin:24px 0;">
                    <a href="{{ $cta_url }}"
                       style="display:inline-block;padding:12px 24px;background:#10b981;color:#ffffff;text-decoration:none;border-radius:999px;font-weight:600;">
                        {{ $cta_text }}
                    </a>
                </p>
            @endif

            @if (!empty($footer))
                <div style="margin-top:24px;padding:12px 16px;background:#f1f5f9;border-radius:8px;font-size:13px;color:#475569;">
                    {{ $footer }}
                </div>
            @endif

            <p style="margin:24px 0 0 0;">
                Trân trọng,<br>
                <strong>{{ config('app.name') }}</strong>
            </p>

            <hr style="margin:24px 0;border:none;border-top:1px solid #e5e7eb;">

            <small style="color:#9ca3af;font-size:12px;line-height:1.6;">
                Bạn nhận được email này vì đã đăng ký nhận thông báo từ {{ config('app.name') }}.
                <br>
                @if (!empty($subscription->verify_token))
                    <a href="{{ route('newsletter.unsubscribe', ['token' => $subscription->verify_token]) }}"
                       style="color:#9ca3af;">Hủy đăng ký</a>
                @endif
            </small>
        </td>
    </tr>
</table>
</body>
</html>


