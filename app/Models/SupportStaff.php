<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportStaff extends Model
{
    use HasFactory;

    protected $table = 'support_staff';

    protected $fillable = [
        'name',
        'role',
        'phone',
        'zalo',
        'color',
        'avatar',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}

