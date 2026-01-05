<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Newsletter extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SUBSCRIBED = 'subscribed';

    public const STATUS_UNSUBSCRIBED = 'unsubscribed';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_SUBSCRIBED,
        self::STATUS_UNSUBSCRIBED,
    ];

    protected $table = 'newsletters';

    protected $fillable = [
        'email',
        'ip',
        'ip_address',
        'user_agent',
        'is_verified',
        'status',
        'source',
        'verify_token',
        'verified_at',
        'unsubscribed_at',
        'note',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    public function scopeSubscribed($query)
    {
        return $query->where('status', self::STATUS_SUBSCRIBED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeUnsubscribed($query)
    {
        return $query->where('status', self::STATUS_UNSUBSCRIBED);
    }

    public function scopeFilterStatus($query, ?string $status)
    {
        if (! $status || ! in_array($status, self::STATUSES, true)) {
            return $query;
        }

        return $query->where('status', $status);
    }

    public function scopeFilterSource($query, ?string $source)
    {
        if (! $source) {
            return $query;
        }

        return $query->where('source', $source);
    }

    public function scopeDateRange($query, ?string $from = null, ?string $to = null)
    {
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query;
    }

    public function scopeSearch($query, ?string $keyword = null)
    {
        if (! $keyword) {
            return $query;
        }

        return $query->where(function ($q) use ($keyword): void {
            $q->where('email', 'like', '%'.$keyword.'%')
                ->orWhere('ip_address', 'like', '%'.$keyword.'%');
        });
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_SUBSCRIBED => 'Đã đăng ký',
            self::STATUS_UNSUBSCRIBED => 'Đã hủy',
            default => 'Chờ xác nhận',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_SUBSCRIBED => 'badge-subscribed',
            self::STATUS_UNSUBSCRIBED => 'badge-unsubscribed',
            default => 'badge-pending',
        };
    }

    public function generateVerifyToken(): void
    {
        $this->verify_token = bin2hex(random_bytes(32));
        $this->save();
    }
}
