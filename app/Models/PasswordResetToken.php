<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PasswordResetToken extends Model
{
    protected $table = 'password_reset_tokens';

    public $incrementing = false;

    protected $primaryKey = 'email';

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'email',
        'token',
        'created_at',
    ];

    /**
     * Create and return a token record for an email.
     */
    public static function createTokenFor(string $email, int $length = 64): self
    {
        $token = Str::random($length);
        $now = Carbon::now();

        return static::create([
            'email' => $email,
            'token' => $token,
            'created_at' => $now,
        ]);
    }

    /**
     * Check if token is expired given a TTL in minutes.
     */
    public function isExpired(int $ttlMinutes = 60): bool
    {
        if (! $this->created_at) {
            return true;
        }

        return Carbon::parse($this->created_at)->addMinutes($ttlMinutes)->isPast();
    }
}
