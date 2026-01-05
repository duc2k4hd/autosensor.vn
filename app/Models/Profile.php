<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;

    protected $table = 'profiles';

    protected $fillable = [
        'account_id',
        'fullname',
        'phone',
        'avatar',
        'gender',
        'birthday',
        'extra',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'birthday' => 'date',
        'extra' => 'array',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Public URL for avatar when possible.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar) {
            return null;
        }

        return '/storage/'.ltrim($this->avatar, '/');
    }
}
