<?php

namespace App\Mail;

use App\Models\Account;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public int $accountId;

    public string $accountEmail;

    public string $accountName;

    public string $accountRole;

    public string $password;

    public string $forgotPasswordUrl;

    private ?Account $account = null;

    public function __construct(Account $account, string $password, string $forgotPasswordUrl)
    {
        // Store all necessary data directly to avoid serialization issues
        $this->accountId = $account->id;
        $this->accountEmail = $account->email;
        $this->accountName = $account->name ?? $account->email;
        $this->accountRole = $account->role;
        $this->password = $password;
        $this->forgotPasswordUrl = $forgotPasswordUrl;
    }

    private function getAccount(): Account
    {
        if ($this->account === null) {
            $this->account = Account::findOrFail($this->accountId);
        }

        return $this->account;
    }

    public function envelope(): Envelope
    {
        $siteName = \App\Models\Setting::where('key', 'site_name')->value('value') ?? config('app.name');
        $fromAddress = config('mail.from.address');
        $fromName = config('mail.from.name', $siteName);

        // Ensure we have a valid from address
        if (empty($fromAddress)) {
            $appUrl = config('app.url', 'http://localhost');
            $host = parse_url($appUrl, PHP_URL_HOST);
            $fromAddress = env('MAIL_FROM_ADDRESS', 'noreply@'.($host ?: 'localhost'));
        }

        // Final fallback
        if (empty($fromAddress) || ! filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) {
            $fromAddress = 'noreply@localhost';
        }

        return new Envelope(
            from: new Address($fromAddress, $fromName),
            subject: "Thông tin tài khoản mới - {$siteName}",
        );
    }

    public function content(): Content
    {
        $siteName = \App\Models\Setting::where('key', 'site_name')->value('value') ?? config('app.name');
        $siteUrl = config('app.url');

        // Use stored values directly, don't reload from database to avoid any confusion
        return new Content(
            view: 'emails.accounts.created',
            with: [
                'accountEmail' => $this->accountEmail,
                'accountName' => $this->accountName,
                'accountRole' => $this->accountRole,
                'password' => $this->password,
                'forgotPasswordUrl' => $this->forgotPasswordUrl,
                'siteName' => $siteName,
                'siteUrl' => $siteUrl,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
