<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'account_id',
        'type',
        'title',
        'message',
        'data',
        'link',
        'icon',
        'priority',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Notification types
    const TYPE_ORDER_NEW = 'order_new';

    const TYPE_ORDER_STATUS = 'order_status';

    const TYPE_ORDER_PAYMENT = 'order_payment';

    const TYPE_COMMENT_NEW = 'comment_new';

    const TYPE_COMMENT_APPROVED = 'comment_approved';

    const TYPE_CONTACT_NEW = 'contact_new';

    const TYPE_VOUCHER_NEW = 'voucher_new';

    const TYPE_FLASH_SALE_START = 'flash_sale_start';

    const TYPE_NEWSLETTER = 'newsletter';

    const TYPE_STOCK_LOW = 'stock_low';

    const TYPE_STOCK_OUT = 'stock_out';

    const TYPE_SYSTEM = 'system';

    // Priorities
    const PRIORITY_LOW = 'low';

    const PRIORITY_NORMAL = 'normal';

    const PRIORITY_HIGH = 'high';

    const PRIORITY_URGENT = 'urgent';

    /**
     * Relationship với Account
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Scope: Lọc theo account
     */
    public function scopeForAccount($query, ?int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope: Thông báo chưa đọc
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope: Thông báo đã đọc
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope: Lọc theo type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Lọc theo priority
     */
    public function scopePriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope: Thông báo cho admin (account_id = null)
     */
    public function scopeForAdmins($query)
    {
        return $query->whereNull('account_id');
    }

    /**
     * Đánh dấu đã đọc
     */
    public function markAsRead(): bool
    {
        return $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Đánh dấu chưa đọc
     */
    public function markAsUnread(): bool
    {
        return $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Get icon mặc định theo type
     */
    public function getDefaultIcon(): string
    {
        return match ($this->type) {
            self::TYPE_ORDER_NEW, self::TYPE_ORDER_STATUS, self::TYPE_ORDER_PAYMENT => 'fa-shopping-cart',
            self::TYPE_COMMENT_NEW, self::TYPE_COMMENT_APPROVED => 'fa-comment',
            self::TYPE_CONTACT_NEW => 'fa-envelope',
            self::TYPE_VOUCHER_NEW => 'fa-tag',
            self::TYPE_FLASH_SALE_START => 'fa-bolt',
            self::TYPE_NEWSLETTER => 'fa-paper-plane',
            default => 'fa-bell',
        };
    }

    /**
     * Get icon để hiển thị
     */
    public function getIconAttribute(?string $value): string
    {
        return $value ?? $this->getDefaultIcon();
    }

    /**
     * Get badge class theo priority
     */
    public function getPriorityBadgeClass(): string
    {
        return match ($this->priority) {
            self::PRIORITY_URGENT => 'badge-danger',
            self::PRIORITY_HIGH => 'badge-warning',
            self::PRIORITY_NORMAL => 'badge-info',
            self::PRIORITY_LOW => 'badge-secondary',
            default => 'badge-secondary',
        };
    }
}
