<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoucherUserUsage extends Model
{
    use HasFactory;

    protected $table = 'voucher_user_usages';

    protected $fillable = [
        'voucher_id',
        'account_id',
        'session_id',
        'usage_count',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'usage_count' => 'integer',
    ];

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Increment usage count for a voucher/account/session.
     * Returns the model after increment.
     */
    public static function incrementFor(int $voucherId, ?int $accountId = null, ?string $sessionId = null, int $by = 1)
    {
        $query = static::where('voucher_id', $voucherId);

        if ($accountId) {
            $query->where('account_id', $accountId);
        } else {
            $query->whereNull('account_id');
        }

        if ($sessionId) {
            $query->where('session_id', $sessionId);
        } else {
            $query->whereNull('session_id');
        }

        $usage = $query->first();

        if (! $usage) {
            $usage = static::create([
                'voucher_id' => $voucherId,
                'account_id' => $accountId,
                'session_id' => $sessionId,
                'usage_count' => max(0, $by),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            return $usage;
        }

        $usage->usage_count = max(0, $usage->usage_count + $by);
        $usage->updated_at = Carbon::now();
        $usage->save();

        return $usage;
    }
}
