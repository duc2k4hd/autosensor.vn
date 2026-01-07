<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlugIndex extends Model
{
    use HasFactory;

    // Mặc định Laravel sẽ pluralize thành "slug_indices", nhưng migration tạo "slug_indexes"
    protected $table = 'slug_indexes';

    protected $fillable = [
        'slug',
        'type',
        'entity_id',
        'is_active',
        'target_slug',
    ];
}

