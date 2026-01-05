<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductHowTo extends Model
{
    use HasFactory;

    protected $table = 'product_how_tos';

    protected $fillable = [
        'product_id',
        'title',
        'description',
        'steps',
        'supplies',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'steps' => 'array',
        'supplies' => 'array',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
