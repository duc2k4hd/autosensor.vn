<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;

class Account extends Authenticatable
{
    use HasFactory, SoftDeletes;

    protected $table = 'accounts';

    const ROLE_USER = 'user';

    const ROLE_ADMIN = 'admin';

    const ROLE_WRITER = 'writer';

    const STATUS_ACTIVE = 'active';

    const STATUS_INACTIVE = 'inactive';

    const STATUS_BANNED = 'banned';

    const STATUS_LOCKED = 'locked';

    const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'email_verified_at',
        'password',
        'role',
        'remember_token',
        'last_password_changed_at',
        'login_attempts',
        'status',
        'security_flags',
        'login_history',
        'logs',
        'admin_note',
        'tags',
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_password_changed_at' => 'datetime',
        'security_flags' => 'array',
        'login_history' => 'datetime',
        'login_attempts' => 'integer',
        'tags' => 'array',
        'deleted_at' => 'datetime',
    ];

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    public function affiliates()
    {
        return $this->hasMany(Affiliate::class);
    }

    public function accountLogs()
    {
        return $this->hasMany(AccountLog::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function logs()
    {
        return $this->hasMany(AccountLog::class);
    }

    public function emailVerifications()
    {
        return $this->hasMany(AccountEmailVerification::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'account_id');
    }

    public function posts()
    {
        return $this->hasMany(Post::class, 'account_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'account_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isWriter(): bool
    {
        return $this->role === self::ROLE_WRITER;
    }

    public function isUser(): bool
    {
        return $this->role === self::ROLE_USER;
    }

    public function isAdminOrWriter(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_WRITER]);
    }

    public static function roles(): array
    {
        return [
            self::ROLE_USER,
            self::ROLE_ADMIN,
            self::ROLE_WRITER,
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_INACTIVE,
            self::STATUS_SUSPENDED,
            self::STATUS_LOCKED,
            self::STATUS_BANNED,
        ];
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isBanned(): bool
    {
        return $this->status === self::STATUS_BANNED;
    }

    public function isLocked(): bool
    {
        return $this->status === self::STATUS_LOCKED;
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeBanned($query)
    {
        return $query->where('status', self::STATUS_BANNED);
    }

    public function scopeLocked($query)
    {
        return $query->where('status', self::STATUS_LOCKED);
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    public function scopeUnverified($query)
    {
        return $query->whereNull('email_verified_at');
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeSearch($query, string $keyword)
    {
        return $query->where(function ($q) use ($keyword) {
            $q->where('name', 'like', "%{$keyword}%")
                ->orWhere('email', 'like', "%{$keyword}%")
                ->orWhere('phone', 'like', "%{$keyword}%");
        });
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getAccountStatusAttribute(): ?string
    {
        return $this->status;
    }
}
