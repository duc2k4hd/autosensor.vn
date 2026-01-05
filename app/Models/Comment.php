<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    // Constants for commentable types
    public const TYPE_POST = 'post';

    public const TYPE_PRODUCT = 'product';

    public const TYPES = [
        self::TYPE_POST => \App\Models\Post::class,
        self::TYPE_PRODUCT => \App\Models\Product::class,
    ];

    protected $table = 'comments';

    protected $fillable = [
        'account_id',
        'session_id',
        'commentable_id',
        'commentable_type',
        'parent_id',
        'content',
        'name',
        'email',
        'is_approved',
        'ip',
        'rating',
        'user_agent',
        'is_reported',
        'reports_count',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'is_reported' => 'boolean',
        'rating' => 'integer',
        'reports_count' => 'integer',
    ];

    protected $appends = [
        'reply_content',
    ];

    // Relationships
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function commentable()
    {
        return $this->morphTo();
    }

    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    public function adminReply()
    {
        return $this->hasOne(Comment::class, 'parent_id')
            ->whereNotNull('account_id')
            ->whereHas('account', function ($q) {
                $q->where('role', 'admin');
            });
    }

    // Scopes
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('is_approved', true);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('is_approved', false);
    }

    public function scopeFilterType(Builder $query, ?string $type): Builder
    {
        if ($type) {
            $query->where('commentable_type', $type);
        }

        return $query;
    }

    public function scopeFilterObjectId(Builder $query, ?int $objectId): Builder
    {
        if ($objectId) {
            $query->where('commentable_id', $objectId);
        }

        return $query;
    }

    public function scopeFilterRating(Builder $query, ?int $rating): Builder
    {
        if ($rating) {
            $query->where('rating', $rating);
        }

        return $query;
    }

    public function scopeFilterStatus(Builder $query, ?string $status): Builder
    {
        if ($status === 'approved') {
            $query->where('is_approved', true);
        } elseif ($status === 'pending') {
            $query->where('is_approved', false);
        }

        return $query;
    }

    public function scopeFilterSearch(Builder $query, ?string $search): Builder
    {
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('content', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function scopeOnlyRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOnlyReplies(Builder $query): Builder
    {
        return $query->whereNotNull('parent_id');
    }

    // Static methods for type management
    public static function typeOptions(): array
    {
        return self::TYPES;
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            self::TYPE_POST => 'Bài viết',
            self::TYPE_PRODUCT => 'Sản phẩm',
            default => ucfirst($type),
        };
    }

    // Accessors
    public function getGuestNameAttribute(): ?string
    {
        return $this->name;
    }

    public function getGuestEmailAttribute(): ?string
    {
        return $this->email;
    }

    public function getIpAddressAttribute(): ?string
    {
        return $this->ip;
    }

    public function getReplyContentAttribute(): ?string
    {
        $reply = $this->adminReply()->first();

        return $reply?->content;
    }

    public function getTypeAttribute(): string
    {
        return $this->commentable_type;
    }

    public function getObjectIdAttribute(): int
    {
        return $this->commentable_id;
    }
}
