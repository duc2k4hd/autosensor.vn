<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostRevision extends Model
{
    use HasFactory;

    protected $table = 'post_revisions';

    protected $fillable = [
        'post_id',
        'edited_by',
        'title',
        'content',
        'excerpt',
        'meta',
        'is_autosave',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'is_autosave' => 'boolean',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function editor()
    {
        return $this->belongsTo(Account::class, 'edited_by');
    }
}
