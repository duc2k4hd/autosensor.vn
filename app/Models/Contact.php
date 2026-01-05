<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_NEW = 'new';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_DONE = 'done';

    public const STATUS_SPAM = 'spam';

    public const STATUSES = [
        self::STATUS_NEW,
        self::STATUS_PROCESSING,
        self::STATUS_DONE,
        self::STATUS_SPAM,
    ];

    protected $table = 'contacts';

    protected $fillable = [
        'account_id',
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'attachment_path',
        'ip',
        'is_read',
        'status',
        'source',
        'admin_note',
        'last_replied_at',
        'reply_count',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'last_replied_at' => 'datetime',
        'reply_count' => 'integer',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function replies()
    {
        return $this->hasMany(ContactReply::class);
    }

    public function scopeSearch($query, ?string $keyword = null)
    {
        if (! $keyword) {
            return $query;
        }

        return $query->where(function ($q) use ($keyword): void {
            $q->where('name', 'like', '%'.$keyword.'%')
                ->orWhere('email', 'like', '%'.$keyword.'%')
                ->orWhere('phone', 'like', '%'.$keyword.'%')
                ->orWhere('subject', 'like', '%'.$keyword.'%')
                ->orWhere('message', 'like', '%'.$keyword.'%');
        });
    }

    public function scopeByStatus($query, ?string $status = null)
    {
        if (! $status || ! in_array($status, self::STATUSES, true)) {
            return $query;
        }

        return $query->where('status', $status);
    }

    public function scopeBySource($query, ?string $source = null)
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

    public function scopeNew($query)
    {
        return $query->where('status', self::STATUS_NEW);
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    public function scopeDone($query)
    {
        return $query->where('status', self::STATUS_DONE);
    }

    public function scopeSpam($query)
    {
        return $query->where('status', self::STATUS_SPAM);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PROCESSING => 'Đang xử lý',
            self::STATUS_DONE => 'Đã xử lý',
            self::STATUS_SPAM => 'Spam',
            default => 'Mới',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PROCESSING => 'badge bg-warning',
            self::STATUS_DONE => 'badge bg-success',
            self::STATUS_SPAM => 'badge bg-danger',
            default => 'badge bg-secondary',
        };
    }
}
