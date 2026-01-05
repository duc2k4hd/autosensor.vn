<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    protected $table = 'product_variants';

    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'price',
        'sale_price',
        'cost_price',
        'stock_quantity',
        'image_id',
        'attributes',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'attributes' => 'array',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'sort_order' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function primaryVariantImage()
    {
        return $this->belongsTo(Image::class, 'image_id');
    }

    /**
     * Lấy giá hiển thị (ưu tiên sale_price, nếu không có thì dùng price)
     */
    public function getDisplayPriceAttribute(): float
    {
        return (float) ($this->sale_price ?? $this->price);
    }

    /**
     * Kiểm tra variant có đang giảm giá không
     */
    public function isOnSale(): bool
    {
        return $this->sale_price !== null && $this->sale_price < $this->price;
    }

    /**
     * Lấy phần trăm giảm giá
     */
    public function getDiscountPercentAttribute(): ?int
    {
        if (! $this->isOnSale()) {
            return null;
        }

        return (int) round((($this->price - $this->sale_price) / $this->price) * 100);
    }

    /**
     * Kiểm tra còn hàng không
     */
    public function isInStock(): bool
    {
        if ($this->stock_quantity === null) {
            return true; // Không giới hạn
        }

        return $this->stock_quantity > 0;
    }

    /**
     * Lấy số lượng còn lại
     */
    public function getRemainingStockAttribute(): ?int
    {
        return $this->stock_quantity;
    }
}
