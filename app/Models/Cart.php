<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasFactory;

    protected $table = 'carts';

    protected $fillable = [
        'account_id',
        'session_id',
        'product_id',
        'options',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'options' => 'array',
        'quantity' => 'integer',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeBySession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function getTotalQuantityAttribute(): int
    {
        return (int) $this->items()->sum('quantity');
    }

    public function getTotalPriceAttribute(): float
    {
        return (float) $this->items()
            ->selectRaw('COALESCE(SUM(quantity * price), 0) as total_price')
            ->value('total_price');
    }
}
