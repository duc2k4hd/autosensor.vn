<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SitemapExclude extends Model
{
    use HasFactory;

    protected $table = 'sitemap_excludes';

    protected $fillable = [
        'type',
        'value',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope active excludes.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
