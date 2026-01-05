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
     * We deliberately do not prepend any folders here so that
     * Blade views can build the final URL, e.g.:
     * asset('clients/assets/img/clothes/' . $image->url)
     */
    public function getUrlAttribute(?string $value): ?string
    {
        return $value ?: null;
    }
}
