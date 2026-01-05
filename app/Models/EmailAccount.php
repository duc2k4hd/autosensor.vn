<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'emails';

    protected $fillable = [
        'email',
        'name',
        'description',
        'is_default',
        'is_active',
        'order',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_encryption',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'order' => 'integer',
        'mail_port' => 'integer',
    ];

    protected $hidden = [
        'mail_password',
    ];

    /**
     * Scope default email account.
     */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true)->orderBy('order');
    }

    /**
     * Scope active senders.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('order');
    }

    /**
     * Lấy email mặc định đang hoạt động.
     */
    public static function getDefault(): ?self
    {
        return static::query()
            ->active()
            ->default()
            ->first();
    }

    /**
     * Lấy danh sách tất cả email đang hoạt động.
     */
    public static function getActiveEmails()
    {
        return static::query()
            ->active()
            ->get();
    }
}
