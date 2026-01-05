<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public ?string $checkoutUrl = null
    ) {}

    public function envelope(): Envelope
    {
        $siteName = \App\Models\Setting::where('key', 'site_name')->value('value') ?? config('app.name');
        $fromAddress = config('mail.from.address');
        $fromName = config('mail.from.name', $siteName);

        // Đảm bảo luôn có fromAddress hợp lệ
        if (empty($fromAddress) || ! filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) {
            $appUrl = config('app.url', 'http://localhost');
            $host = parse_url($appUrl, PHP_URL_HOST);
            $fromAddress = env('MAIL_FROM_ADDRESS');

            // Nếu vẫn không có, tạo từ host
            if (empty($fromAddress) || ! filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) {
                $fromAddress = 'noreply@'.($host ?: 'localhost');
            }

            // Nếu vẫn không hợp lệ, dùng giá trị mặc định
            if (! filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) {
                $fromAddress = 'noreply@autosensor.vn';
            }
        }

        // Đảm bảo fromName không rỗng
        if (empty($fromName)) {
            $fromName = $siteName ?: 'AutoSensor Việt Nam';
        }

        $toEmail = $this->order->receiver_email ?? $this->order->account?->email;

        // Đảm bảo email hợp lệ
        if (! $toEmail || ! filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email address for order notification');
        }

        // Lấy tên nhưng chỉ dùng nếu không có ký tự đặc biệt gây lỗi
        $toName = $this->order->receiver_name ?? $this->order->account?->name;
        if ($toName) {
            $toName = trim($toName);
            // Chỉ dùng tên nếu là ASCII hoặc có thể encode được
            // Nếu không, để null để tránh lỗi RFC 2822
            if (preg_match('/[^\x00-\x7F]/', $toName)) {
                // Có ký tự non-ASCII, thử encode
                try {
                    $encodedName = mb_encode_mimeheader($toName, 'UTF-8', 'Q');
                    $toName = $encodedName;
                } catch (\Throwable $e) {
                    // Nếu encode lỗi, không dùng tên
                    $toName = null;
                }
            }
        } else {
            $toName = null;
        }

        return new Envelope(
            from: new Address($fromAddress, $fromName),
            to: [$toName ? new Address($toEmail, $toName) : new Address($toEmail)],
            subject: "Đơn hàng #{$this->order->code} đã được tạo - {$siteName}",
        );
    }

    public function content(): Content
    {
        $siteName = \App\Models\Setting::where('key', 'site_name')->value('value') ?? config('app.name');
        $siteUrl = config('app.url');

        return new Content(
            view: 'emails.orders.created',
            with: [
                'order' => $this->order,
                'siteName' => $siteName,
                'siteUrl' => $siteUrl,
                'checkoutUrl' => $this->checkoutUrl,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
