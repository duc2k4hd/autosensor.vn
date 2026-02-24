<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerConsultationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $lead;
    public $product;
    public $brand;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($lead, $product = null, $brand = null)
    {
        $this->lead = $lead;
        $this->product = $product;
        $this->brand = $brand;
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
                    ->subject('Xác nhận yêu cầu tư vấn từ AutoSensor Việt Nam')
                    ->view('clients.emails.customer_consultation');
    }
}
