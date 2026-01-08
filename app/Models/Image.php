<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Image extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'images';

    protected $fillable = [
        'url',
        'title',
        'notes',
        'alt',
        'is_primary',
        'order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Accessor for the raw image file name / relative path stored in DB.
     *
     * Normalize URL để đảm bảo chỉ trả về tên file (basename), không có path.
     * Blade views có thể build final URL: asset('clients/assets/img/clothes/' . $image->url)
     */
    public function getUrlAttribute(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        
        // Normalize: loại bỏ leading slash và prefix "clients/assets/img/clothes/" nếu có
        $normalized = ltrim($value, '/');
        $normalized = preg_replace('#^clients/assets/img/clothes/#', '', $normalized);
        
        // Loại bỏ các path khác như "thumbs/" nếu có
        $normalized = basename($normalized);
        
        return $normalized ?: null;
    }
}
