<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FlashSale extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'flash_sales';

    protected $fillable = [
        'title',
        'description',
        'banner',
        'tag',
        'start_time',
        'end_time',
        'status',
        'is_active',
        'created_by',
        'max_per_user',
        'display_limit',
        'product_add_mode',
        'views',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_active' => 'boolean',
        'views' => 'integer',
        'max_per_user' => 'integer',
        'display_limit' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(FlashSaleItem::class);
    }

    /**
     * Scope currently active flash sales.
     */
    public function scopeActive($query)
    {
        $now = Carbon::now();

        return $query->where('is_active', true)
            ->where('status', 'active')
            ->where(function ($q) use ($now) {
                $q->whereNull('start_time')->orWhere('start_time', '<=', $now);
            })->where(function ($q) use ($now) {
                $q->whereNull('end_time')->orWhere('end_time', '>=', $now);
            });
    }

    /**
     * Is this flash sale active now?
     */
    public function isActiveNow(): bool
    {
        $now = Carbon::now();
        if (! $this->is_active) {
            return false;
        }

        if ($this->status !== 'active') {
            return false;
        }

        if ($this->start_time && $now->lt($this->start_time)) {
            return false;
        }

        if ($this->end_time && $now->gt($this->end_time)) {
            return false;
        }

        return true;
    }

    /**
     * Alias for isActiveNow()
     */
    public function isActive(): bool
    {
        return $this->isActiveNow();
    }

    /**
     * Check if flash sale is expired
     */
    public function isExpired(): bool
    {
        if (! $this->end_time) {
            return false;
        }

        return Carbon::now()->gt($this->end_time);
    }

    /**
     * Check if flash sale is upcoming
     */
    public function isUpcoming(): bool
    {
        if (! $this->start_time) {
            return false;
        }

        return Carbon::now()->lt($this->start_time) && $this->is_active && $this->status === 'active';
    }

    /**
     * Check if flash sale can be edited
     */
    public function canEdit(): bool
    {
        // Có thể edit nếu chưa bắt đầu hoặc đã kết thúc
        if ($this->isExpired()) {
            return true;
        }

        if ($this->isUpcoming()) {
            return true;
        }

        // Không thể edit nếu đang chạy
        return ! $this->isActive();
    }

    /**
     * Auto lock flash sale when it's running
     * Note: is_locked field không có trong migration, có thể bỏ qua hoặc thêm migration sau
     */
    public function autoLock(): void
    {
        // Tạm thời không làm gì vì không có field is_locked
        // Có thể thêm migration sau nếu cần
    }

    /**
     * Get active items only
     */
    public function activeItems()
    {
        return $this->items()->where('is_active', true);
    }

    /**
     * Get total products count
     */
    public function getTotalProductsAttribute(): int
    {
        return $this->items()->count();
    }

    /**
     * Get total sold count
     */
    public function getTotalSoldAttribute(): int
    {
        return $this->items()->sum('sold');
    }

    /**
     * Get total remaining count
     */
    public function getTotalRemainingAttribute(): int
    {
        $items = $this->items;

        return $items->sum(function ($item) {
            return max(0, ($item->stock ?? 0) - ($item->sold ?? 0));
        });
    }

    /**
     * Get remaining time in seconds
     */
    public function getRemainingTimeAttribute(): ?int
    {
        if (! $this->end_time) {
            return null;
        }

        $now = Carbon::now();
        if ($now->gt($this->end_time)) {
            return 0;
        }

        return max(0, $now->diffInSeconds($this->end_time, false));
    }
}
