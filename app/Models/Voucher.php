<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;

    // ==============================
    // CONSTANTS
    // ==============================

    // Status constants (computed from is_active + start_time/end_time)
    public const STATUS_ACTIVE = 'active';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_DISABLED = 'disabled';

    // Type constants (from migration enum)
    public const TYPE_PERCENT = 'percent';

    public const TYPE_FIXED = 'fixed';

    public const TYPE_FREE_SHIPPING = 'free_shipping';

    // Applicable constants (for apply_for JSON field)
    public const APPLICABLE_ALL = 'all';

    public const APPLICABLE_CATEGORIES = 'categories';

    public const APPLICABLE_PRODUCTS = 'products';

    protected $fillable = [
        'code', 'name', 'description', 'image', 'account_id', 'updated_by',
        'type', 'value', 'max_discount', 'min_order_value',
        'usage_limit', 'usage_limit_per_user',
        'start_time', 'end_time', 'is_active', 'apply_for',
    ];

    protected $casts = [
        'apply_for' => 'array',
        'is_active' => 'boolean',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'value' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'min_order_value' => 'decimal:2',
    ];

    protected $appends = [
        'status',
        'usage_count',
    ];

    // ==============================
    // QUAN HỆ
    // ==============================

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(Account::class, 'updated_by');
    }

    public function histories()
    {
        return $this->hasMany(VoucherHistory::class);
    }

    public function userUsages()
    {
        return $this->hasMany(VoucherUserUsage::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // ==============================
    // SCOPES
    // ==============================

    /**
     * Voucher đang hoạt động và trong thời gian hợp lệ
     */
    public function scopeActive($query)
    {
        $now = Carbon::now();

        return $query
            ->where('is_active', 1)

            // Thời gian bắt đầu
            ->where(function ($q) use ($now) {
                $q->whereNull('start_time')
                    ->orWhere('start_time', '<=', $now);
            })

            // Thời gian kết thúc
            ->where(function ($q) use ($now) {
                $q->whereNull('end_time')
                    ->orWhere('end_time', '>=', $now);
            })

            ->where(function ($q) {
                $q->whereNull('usage_limit')
                    ->orWhereRaw('usage_limit > (SELECT COUNT(*) FROM voucher_histories WHERE voucher_histories.voucher_id = vouchers.id)');
            });
    }

    /**
     * Áp dụng theo User hoặc session visitor
     */
    public function scopeAvailableForUser($query, $userId = null, $sessionId = null)
    {
        return $query->where(function ($q) use ($userId, $sessionId) {

            $q->whereNull('usage_limit_per_user') // Không giới hạn
                ->orWhere(function ($q2) use ($userId, $sessionId) {

                    // Query ra số lần user đã dùng
                    $q2->where('usage_limit_per_user', '>', function ($sub) use ($userId, $sessionId) {
                        $sub->from('voucher_user_usages')
                            ->whereColumn('voucher_user_usages.voucher_id', 'vouchers.id')
                            ->when($userId, fn ($q) => $q->where('account_id', $userId))
                            ->when(! $userId, fn ($q) => $q->where('session_id', $sessionId))
                            ->selectRaw('COALESCE(SUM(usage_count), 0)');
                    });

                });

        });
    }

    // ==============================
    // ACCESSORS
    // ==============================

    /**
     * Get computed status based on is_active + start_time/end_time
     */
    public function getStatusAttribute(): string
    {
        if (! $this->is_active) {
            return self::STATUS_DISABLED;
        }

        $now = Carbon::now();

        if ($this->start_time && $now->lt($this->start_time)) {
            return self::STATUS_SCHEDULED;
        }

        if ($this->end_time && $now->gt($this->end_time)) {
            return self::STATUS_EXPIRED;
        }

        return self::STATUS_ACTIVE;
    }

    /**
     * Get usage count from histories
     */
    public function getUsageCountAttribute(): int
    {
        return $this->histories()->count();
    }

    /**
     * Alias for start_time (for backward compatibility with admin controller)
     */
    public function getStartAtAttribute(): ?Carbon
    {
        return $this->start_time;
    }

    /**
     * Alias for end_time (for backward compatibility with admin controller)
     */
    public function getEndAtAttribute(): ?Carbon
    {
        return $this->end_time;
    }

    /**
     * Tính tổng lượt sử dụng (dùng cho usage_limit) - backward compatibility
     */
    public function getUsedTotalAttribute(): int
    {
        return $this->usage_count;
    }

    /**
     * Get type label for display
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_PERCENT => 'Giảm %',
            self::TYPE_FIXED => 'Giảm tiền',
            self::TYPE_FREE_SHIPPING => 'Free ship',
            default => 'Không xác định',
        };
    }

    /**
     * Get value label for display
     */
    public function getValueLabelAttribute(): string
    {
        $value = (float) ($this->value ?? 0);
        $maxDiscount = (float) ($this->max_discount ?? 0);

        if ($this->type === self::TYPE_PERCENT) {
            return number_format($value, 0).'%'.($maxDiscount > 0 ? ' (tối đa '.number_format($maxDiscount, 0).'₫)' : '');
        }
        if ($this->type === self::TYPE_FIXED) {
            return number_format($value, 0).'₫';
        }
        if ($this->type === self::TYPE_FREE_SHIPPING) {
            return $value > 0 ? number_format($value, 0).'₫' : 'Miễn phí';
        }

        return number_format($value, 0).'₫';
    }

    /**
     * Get status badge color for display
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'success',
            self::STATUS_SCHEDULED => 'info',
            self::STATUS_EXPIRED => 'secondary',
            self::STATUS_DISABLED => 'warning',
            default => 'secondary',
        };
    }

    // ==============================
    // MUTATORS
    // ==============================

    /**
     * Set status - automatically updates is_active
     */
    public function setStatusAttribute(string $status): void
    {
        switch ($status) {
            case self::STATUS_ACTIVE:
                $this->is_active = true;
                break;
            case self::STATUS_DISABLED:
                $this->is_active = false;
                break;
            case self::STATUS_SCHEDULED:
                $this->is_active = true;
                // start_time sẽ được set riêng
                break;
            case self::STATUS_EXPIRED:
                $this->is_active = false;
                // end_time sẽ được set riêng
                break;
        }
    }

    /**
     * Alias for start_time (for backward compatibility)
     */
    public function setStartAtAttribute($value): void
    {
        $this->start_time = $value;
    }

    /**
     * Alias for end_time (for backward compatibility)
     */
    public function setEndAtAttribute($value): void
    {
        $this->end_time = $value;
    }

    // ==============================
    // METHODS
    // ==============================

    /**
     * Check if voucher is currently active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Refresh computed status (no-op, status is computed via accessor)
     * Kept for backward compatibility with admin controller
     */
    public function refreshComputedStatus(): void
    {
        // Status is computed via accessor, no need to refresh
        // But we can validate logic here if needed
    }

    /**
     * Scope for filtering vouchers
     */
    public function scopeFilter($query, array $filters): void
    {
        // Filter by status (computed from is_active + time)
        if (isset($filters['status'])) {
            $status = $filters['status'];
            $now = Carbon::now();

            match ($status) {
                self::STATUS_ACTIVE => $query->where('is_active', true)
                    ->where(function ($q) use ($now) {
                        $q->whereNull('start_time')->orWhere('start_time', '<=', $now);
                    })
                    ->where(function ($q) use ($now) {
                        $q->whereNull('end_time')->orWhere('end_time', '>=', $now);
                    }),
                self::STATUS_DISABLED => $query->where('is_active', false),
                self::STATUS_SCHEDULED => $query->where('is_active', true)
                    ->where('start_time', '>', $now),
                self::STATUS_EXPIRED => $query->where(function ($q) use ($now) {
                    $q->where('is_active', false)
                        ->orWhere('end_time', '<', $now);
                }),
                default => null,
            };
        }

        // Filter by type
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Filter by search (code, name)
        if (isset($filters['search']) && $filters['search']) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Filter by created_by
        if (isset($filters['created_by'])) {
            $query->where('account_id', $filters['created_by']);
        }

        // Filter by date range
        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
    }

    protected static function booted(): void
    {
        static::saved(function (self $banner) {
            Cache::forget('vouchers_home');
        });

        static::deleted(function (self $banner) {
            Cache::forget('vouchers_home');
        });
    }
}
