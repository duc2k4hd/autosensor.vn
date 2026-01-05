<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashSaleItem extends Model
{
    use HasFactory;

    protected $table = 'flash_sale_items';

    protected $fillable = [
        'flash_sale_id',
        'product_id',
        'original_price',
        'sale_price',
        'unified_price',
        'original_variant_price',
        'stock',
        'sold',
        'max_per_user',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'original_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'unified_price' => 'decimal:2',
        'original_variant_price' => 'decimal:2',
        'stock' => 'integer',
        'sold' => 'integer',
        'max_per_user' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function flashSale(): BelongsTo
    {
        return $this->belongsTo(FlashSale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function priceLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FlashSalePriceLog::class);
    }

    /**
     * Check if item is available for purchase.
     */
    public function isAvailable(): bool
    {
        return $this->is_active && ($this->stock === null || $this->stock > 0);
    }

    /**
     * Reduce stock and increase sold count atomically.
     */
    public function reduceStock(int $qty = 1): bool
    {
        if (! $this->isAvailable()) {
            return false;
        }

        $this->stock = ($this->stock === null) ? null : max(0, $this->stock - $qty);
        if (is_int($this->sold)) {
            $this->sold += $qty;
        } else {
            $this->sold = $qty;
        }

        return $this->save();
    }

    /**
     * Scope: Active items only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Available items (active and in stock)
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('stock')
                    ->orWhereRaw('stock > sold');
            });
    }
}
