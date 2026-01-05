<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsletterCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'subject',
        'content',
        'cta_url',
        'cta_text',
        'footer',
        'filter_status',
        'filter_source',
        'filter_date_from',
        'filter_date_to',
        'total_target',
        'sent_success',
        'sent_failed',
        'status',
        'created_by',
    ];

    protected $casts = [
        'filter_date_from' => 'date',
        'filter_date_to' => 'date',
    ];
}
