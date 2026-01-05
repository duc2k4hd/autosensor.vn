<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';

    protected $fillable = [

        'parent_id',

        'name',

        'slug',

        'description',

        'image',

        'order',

        'is_active',

        'metadata',

        'created_at',

        'updated_at',

    ];

    protected $casts = [

        'is_active' => 'boolean',

        'order' => 'integer',

        'metadata' => 'array',

    ];

    public function products()
    {

        return $this->hasMany(Product::class, 'primary_category_id');

    }

    public function scopeActive($query)
    {

        return $query->where('is_active', true);

    }

    public function parent()
    {

        return $this->belongsTo(Category::class, 'parent_id');

    }

    public function children()
    {

        return $this->hasMany(Category::class, 'parent_id')

            ->active()

            ->orderBy('order')

            ->orderBy('name');

    }

    /**
     * Quan hệ sản phẩm qua cột primary_category_id (tối ưu cho query chính).
     */
    public function primaryProducts()
    {

        return $this->hasMany(Product::class, 'primary_category_id');

    }

    /**
     * Quan hệ sản phẩm qua JSON category_ids (dùng khi cần lấy thêm).
     */
    public function extraProducts()
    {
        // Kiểm tra cả integer và string vì JSON có thể lưu dưới cả hai dạng
        return Product::where(function ($q) {
            $q->whereJsonContains('category_ids', (int) $this->id)
                ->orWhereJsonContains('category_ids', (string) $this->id);
        });
    }

    public function posts()
    {

        return $this->hasMany(Post::class, 'category_id');

    }
}
