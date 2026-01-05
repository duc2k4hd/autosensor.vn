<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountLog extends Model
{
    use HasFactory;

    protected $table = 'account_logs';

    protected $fillable = [
        'account_id',
        'admin_id',
        'type',
        'payload',
        'ip',
        'user_agent',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function admin()
    {
        return $this->belongsTo(Account::class, 'admin_id');
    }
}
