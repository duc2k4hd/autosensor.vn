<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\ContactReply;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;

class ContactReplyService
{
    public function sendReply(Contact $contact, string $message, ?UploadedFile $attachment = null): array
    {
        try {
            $to = trim((string) $contact->email);

            if ($to === '') {
                return [
                    'success' => false,
                    'error' => 'Liên hệ không có email, không thể gửi trả lời.',
                ];
            }

            $fromAddress = trim((string) config('mail.from.address'));
            if ($fromAddress === '') {
                $fromAddress = trim((string) config('mail.username'));
            }
            $fromName = config('mail.from.name') ?: config('app.name');

            if ($fromAddress === '') {
                $fromAddress = 'no-reply@localhost';
            }

            Mail::send([], [], function (Message $mail) use ($to, $message, $attachment, $fromAddress, $fromName): void {
                $mail->to($to)
                    ->from($fromAddress, $fromName)
                    ->subject('Phản hồi liên hệ từ '.config('app.name'))
                    ->html($message);

                if ($attachment instanceof UploadedFile) {
                    $mail->attach($attachment->getRealPath(), [
                        'as' => $attachment->getClientOriginalName(),
                        'mime' => $attachment->getMimeType(),
                    ]);
                }
            });

            $contact->last_replied_at = now();
            $contact->reply_count = (int) $contact->reply_count + 1;
            $contact->status = Contact::STATUS_DONE;
            $contact->is_read = true;
            $contact->save();

            ContactReply::create([
                'contact_id' => $contact->id,
                'account_id' => auth('admin')->id(),
                'message' => $message,
            ]);

            return [
                'success' => true,
                'message' => 'Đã gửi email phản hồi cho khách hàng.',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
