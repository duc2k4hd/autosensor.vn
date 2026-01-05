<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CacheLock extends Model
{
    protected $table = 'cache_locks';

    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'key',
        'owner',
        'expiration',
    ];

    protected $casts = [
        'expiration' => 'integer',
    ];
}
