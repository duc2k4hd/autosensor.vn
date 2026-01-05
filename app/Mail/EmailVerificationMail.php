<?php

namespace App\Mail;

use App\Models\Account;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Account $account;

    public string $verificationUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Account $account, string $verificationUrl)
    {
        $this->account = $account;
        $this->verificationUrl = $verificationUrl;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Xác thực email đăng ký tài khoản - '.(config('site.short_name') ?? 'AutoSensor Việt Nam'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Đảm bảo account được load lại đầy đủ dữ liệu sau khi serialize
        // Khi SerializesModels serialize, nó chỉ lưu ID, nên cần load lại
        if ($this->account && $this->account->exists) {
            // Nếu account chỉ có ID (sau khi deserialize), load lại từ DB
            if ((empty($this->account->name) && empty($this->account->email)) && ! empty($this->account->id)) {
                $this->account = Account::findOrFail($this->account->id);
            } elseif ($this->account->id) {
                // Refresh để đảm bảo có đầy đủ dữ liệu
                $this->account->refresh();
            }
        }

        return new Content(
            view: 'emails.auth.email-verification',
            with: [
                'account' => $this->account,
                'verificationUrl' => $this->verificationUrl,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
