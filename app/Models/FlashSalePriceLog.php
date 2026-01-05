<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashSalePriceLog extends Model
{
    use HasFactory;

    protected $table = 'flash_sale_price_logs';

    protected $fillable = [
        'flash_sale_item_id',
        'old_price',
        'new_price',
        'changed_by',
        'changed_at',
        'reason',
    ];

    protected $casts = [
        'old_price' => 'decimal:2',
        'new_price' => 'decimal:2',
        'changed_at' => 'datetime',
    ];

    public function flashSaleItem(): BelongsTo
    {
        return $this->belongsTo(FlashSaleItem::class);
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'changed_by');
    }
}
