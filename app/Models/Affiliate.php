<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Affiliate extends Model
{
    use HasFactory;

    protected $table = 'affiliates';

    protected $fillable = [
        'account_id',
        'code',
        'clicks',
        'conversions',
        'commission_rate',
        'total_commission',
        'referral_url',
        'status',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'clicks' => 'integer',
        'conversions' => 'integer',
        'commission_rate' => 'float',
        'total_commission' => 'float',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
