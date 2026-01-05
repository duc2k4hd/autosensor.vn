<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductFaq extends Model
{
    use HasFactory;

    protected $table = 'product_faqs';

    protected $fillable = [
        'product_id',
        'question',
        'answer',
        'order',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
