<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PersonalAccessToken extends Model
{
    use HasFactory;

    //
    protected $table = 'personal_access_tokens';

    protected $fillable = [
        'tokenable_id',
        'tokenable_type',
        'name',
        'token',
        'abilities',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'abilities' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Polymorphic relation to the owning model (Account, Session, etc.).
     */
    public function tokenable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope tokens that are not expired.
     */
    public function scopeValid($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', Carbon::now());
        });
    }

    /**
     * Check if this token is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Mark token as used now.
     */
    public function markUsed(): bool
    {
        $this->last_used_at = Carbon::now();

        return $this->save();
    }
}
