<?php

namespace App\Helpers;

use App\Models\EmailAccount;

class EmailHelper
{
    /**
     * Lấy email mặc định hoặc email theo ID
     *
     * @param  int|null  $emailAccountId  ID của email account (null = lấy mặc định)
     */
    public static function getFromEmail(?int $emailAccountId = null): ?string
    {
        if ($emailAccountId) {
            $emailAccount = EmailAccount::active()->find($emailAccountId);

            return $emailAccount?->email;
        }

        $default = EmailAccount::getDefault();

        return $default?->email ?? config('mail.from.address');
    }

    /**
     * Lấy tên hiển thị của email
     *
     * @param  int|null  $emailAccountId  ID của email account (null = lấy mặc định)
     */
    public static function getFromName(?int $emailAccountId = null): ?string
    {
        if ($emailAccountId) {
            $emailAccount = EmailAccount::active()->find($emailAccountId);

            return $emailAccount?->name ?? config('mail.from.name');
        }

        $default = EmailAccount::getDefault();

        return $default?->name ?? config('mail.from.name');
    }

    /**
     * Lấy email account object
     *
     * @param  int|null  $emailAccountId  ID của email account (null = lấy mặc định)
     */
    public static function getEmailAccount(?int $emailAccountId = null): ?EmailAccount
    {
        if ($emailAccountId) {
            return EmailAccount::active()->find($emailAccountId);
        }

        return EmailAccount::getDefault();
    }

    /**
     * Lấy danh sách email đang hoạt động để chọn
     *
     * @return array [id => email (name)]
     */
    public static function getEmailOptions(): array
    {
        $emails = EmailAccount::getActiveEmails();

        // Nếu chưa cấu hình email trong DB, fallback về config .env
        if ($emails->isEmpty()) {
            $fromEmail = config('mail.from.address')
                ?: config('mail.mailers.smtp.username')
                ?: env('MAIL_USERNAME')
                ?: env('MAIL_FROM_ADDRESS')
                ?: 'no-reply@localhost';
            $fromName = config('mail.from.name') ?: config('app.name');

            // id = 0: dùng cấu hình mặc định trong .env
            return [
                0 => "{$fromEmail} ({$fromName})",
            ];
        }

        return $emails->mapWithKeys(function ($email) {
            $name = $email->name ?: $email->email;

            return [$email->id => "{$email->email} ({$name})"];
        })->toArray();
    }
}
