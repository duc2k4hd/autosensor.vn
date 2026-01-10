<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuickConsultationLead extends Model
{
    protected $table = 'quick_consultation_leads';

    protected $fillable = [
        'product_id',
        'name',
        'phone',
        'email',
        'message',
        'trigger_type',
        'behavior_data',
        'ip_address',
        'user_agent',
        'session_id',
        'is_contacted',
        'contacted_at',
    ];

    protected $casts = [
        'behavior_data' => 'array',
        'is_contacted' => 'boolean',
        'contacted_at' => 'datetime',
    ];

    /**
     * Quan hệ với Product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
