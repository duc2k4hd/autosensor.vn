<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductWizardSession extends Model
{
    protected $table = 'product_wizard_sessions';

    protected $fillable = [
        'category_id',
        'answers',
        'recommended_product_ids',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'answers' => 'array',
        'recommended_product_ids' => 'array',
    ];

    /**
     * Quan hệ với Category
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Lấy danh sách sản phẩm được gợi ý
     */
    public function recommendedProducts()
    {
        if (empty($this->recommended_product_ids)) {
            return collect();
        }

        return Product::whereIn('id', $this->recommended_product_ids)
            ->where('is_active', true)
            ->get();
    }
}
