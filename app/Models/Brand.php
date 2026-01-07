<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class Brand extends Model
{
    use HasFactory;

    protected $table = 'brands';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'order',
        'is_active',
        'metadata',
        'website',
        'country',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Quan hệ với sản phẩm
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'brand_id');
    }

    /**
     * Scope lấy các brand đang hoạt động
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope sắp xếp theo order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
    }

    /**
     * Lấy meta title từ metadata hoặc tên brand
     */
    public function getMetaTitleAttribute()
    {
        return $this->metadata['meta_title'] ?? $this->name . ' - AutoSensor Việt Nam';
    }

    /**
     * Lấy meta description từ metadata hoặc description
     */
    public function getMetaDescriptionAttribute()
    {
        return $this->metadata['meta_description'] ?? $this->description ?? '';
    }

    /**
     * Lấy meta keywords từ metadata
     */
    public function getMetaKeywordsAttribute()
    {
        return $this->metadata['meta_keywords'] ?? [];
    }

    /**
     * Đếm số sản phẩm của brand
     */
    public function getProductsCountAttribute()
    {
        return $this->products()->where('is_active', true)->count();
    }
}
