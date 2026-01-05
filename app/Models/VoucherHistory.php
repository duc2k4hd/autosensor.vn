<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoucherHistory extends Model
{
    use HasFactory;

    protected $table = 'voucher_histories';

    protected $fillable = [
        'voucher_id',
        'order_id',
        'account_id',
        'discount_amount',
        'ip',
        'session_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
    ];

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Record a voucher usage entry.
     *
     * @return static
     */
    public static function recordUsage(int $voucherId, ?int $orderId = null, ?int $accountId = null, float $discountAmount = 0.0, ?string $ip = null, ?string $sessionId = null)
    {
        return static::create([
            'voucher_id' => $voucherId,
            'order_id' => $orderId,
            'account_id' => $accountId,
            'discount_amount' => $discountAmount,
            'ip' => $ip,
            'session_id' => $sessionId,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
