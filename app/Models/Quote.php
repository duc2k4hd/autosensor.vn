<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{
    use HasFactory;

    protected $table = 'quotes';

    protected $fillable = [
        'account_id',
        'name',
        'email',
        'phone',
        'note',
        'total_amount',
        'cart_snapshot',
        'status',
        'pdf_path',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'cart_snapshot' => 'array',
        'total_amount' => 'decimal:2',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}

