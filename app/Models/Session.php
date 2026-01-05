<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Session extends Model
{
    protected $table = 'sessions';

    protected $fillable = [
        'id',
        'account_id',
        'ip_address',
        'user_agent',
        'payload',
        'last_activity',
    ];

    protected $casts = [
        'last_activity' => 'integer',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    /**
     * Relation to account (may be null for guest sessions).
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * Scope sessions for an account or session id.
     */
    public function scopeForAccount($query, $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope recent active sessions. Default 30 minutes.
     */
    public function scopeRecentlyActive($query, int $seconds = 1800)
    {
        $cutoff = Carbon::now()->subSeconds($seconds)->getTimestamp();

        return $query->where('last_activity', '>=', $cutoff);
    }
}
