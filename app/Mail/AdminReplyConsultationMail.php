<?php

namespace App\Mail;

use App\Models\QuickConsultationLead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminReplyConsultationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $lead;
    public $replyContent;
    public $subject;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(QuickConsultationLead $lead, $replyContent, $subject = null)
    {
        $this->lead = $lead;
        $this->replyContent = $replyContent;
        $this->subject = $subject ?? 'Phản hồi yêu cầu tư vấn từ AutoSensor Việt Nam';
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(config('mail.from.address'), config('mail.from.name'))
                    ->replyTo(config('mail.from.address'), config('mail.from.name'))
                    ->subject($this->subject)
                    ->view('clients.emails.admin_reply_consultation');
    }
}
