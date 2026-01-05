<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    protected $table = 'inventory_movements';

    protected $fillable = [
        'product_id',
        'quantity_change',
        'stock_before',
        'stock_after',
        'type',
        'reference_type',
        'reference_id',
        'account_id',
        'note',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'quantity_change' => 'integer',
        'stock_before' => 'integer',
        'stock_after' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
