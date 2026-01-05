<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'code',
        'account_id',
        'session_id',
        'shipping_address_id',
        'billing_address_id',
        'total_price',
        'shipping_fee',
        'tax',
        'discount',
        'voucher_discount',
        'voucher_code',
        'affiliate_code',
        'final_price',
        'receiver_name',
        'receiver_phone',
        'receiver_email',
        'shipping_address',
        'shipping_province_id',
        'shipping_district_id',
        'shipping_ward_id',
        'payment_method',
        'payment_status',
        'transaction_code',
        'shipping_partner',
        'shipping_tracking_code',
        'shipping_raw_response',
        'delivery_status',
        'status',
        'is_flash_sale',
        'customer_note',
        'admin_note',
        'ip',
        'voucher_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'voucher_discount' => 'decimal:2',
        'final_price' => 'decimal:2',
        'shipping_raw_response' => 'array',
        'is_flash_sale' => 'boolean',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function shippingAddress()
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    public function billingAddress()
    {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function voucherHistories()
    {
        return $this->hasMany(VoucherHistory::class);
    }

    /**
     * Scope orders for an account or session.
     */
    public function scopeForUser($query, $accountId = null, $sessionId = null)
    {
        if ($accountId) {
            $query->where('account_id', $accountId);
        }

        if ($sessionId) {
            $query->orWhere('session_id', $sessionId);
        }

        return $query;
    }

    /**
     * Getter cho final_price để đảm bảo luôn có giá trị hợp lệ.
     */
    public function getFinalPriceAttribute($value): float
    {
        if (! is_null($value)) {
            return (float) $value;
        }

        $subtotal = (float) ($this->total_price ?? 0);
        $shipping = (float) ($this->shipping_fee ?? 0);
        $tax = (float) ($this->tax ?? 0);
        $discount = (float) ($this->discount ?? 0);
        $voucher = (float) ($this->voucher_discount ?? 0);

        return max($subtotal + $shipping + $tax - $discount - $voucher, 0);
    }

    public function canCancel(): bool
    {
        if (in_array($this->status, ['cancelled', 'completed'], true)) {
            return false;
        }

        return true;
    }

    public function canCreateGhnShipment(): bool
    {
        if ($this->shipping_partner !== 'ghn') {
            return false;
        }

        if ($this->shipping_tracking_code) {
            return false;
        }

        if ($this->status === 'cancelled') {
            return false;
        }

        // Lấy ID từ order hoặc fallback từ shippingAddress
        $provinceId = $this->shipping_province_id ?? $this->shippingAddress?->province_code;
        $districtId = $this->shipping_district_id ?? $this->shippingAddress?->district_code;
        $wardId = $this->shipping_ward_id ?? $this->shippingAddress?->ward_code;
        $address = $this->shipping_address ?? $this->shippingAddress?->detail_address;

        $hasAddress = $this->receiver_name
            && $this->receiver_phone
            && $address
            && $provinceId
            && $districtId
            && $wardId;

        return (bool) $hasAddress;
    }

    public function getShippingStatusHistoryAttribute(): array
    {
        $raw = $this->shipping_raw_response;

        if (is_array($raw) && isset($raw['status_history']) && is_array($raw['status_history'])) {
            return $raw['status_history'];
        }

        return [];
    }

    public function getCurrentShippingStatusMetaAttribute(): ?array
    {
        $raw = $this->shipping_raw_response;
        $status = is_array($raw) ? ($raw['current_status'] ?? null) : null;

        if (! $status) {
            return null;
        }

        $definitions = config('ghn.shipping_statuses', []);
        $definition = $definitions[$status] ?? [];

        return [
            'status' => $status,
            'label' => $definition['label'] ?? str_replace('_', ' ', ucfirst($status)),
            'description' => $definition['description'] ?? null,
            'delivery_bucket' => $definition['delivery_bucket'] ?? null,
        ];
    }
}
