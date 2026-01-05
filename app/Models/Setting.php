<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    // Constants for types
    public const TYPE_STRING = 'string';

    public const TYPE_TEXT = 'text';

    public const TYPE_TEXTAREA = 'textarea';

    public const TYPE_INTEGER = 'integer';

    public const TYPE_FLOAT = 'float';

    public const TYPE_NUMBER = 'number';

    public const TYPE_BOOLEAN = 'boolean';

    public const TYPE_JSON = 'json';

    public const TYPE_EMAIL = 'email';

    public const TYPE_URL = 'url';

    public const TYPE_IMAGE = 'image';

    public const TYPES = [
        self::TYPE_STRING,
        self::TYPE_TEXT,
        self::TYPE_TEXTAREA,
        self::TYPE_INTEGER,
        self::TYPE_FLOAT,
        self::TYPE_NUMBER,
        self::TYPE_BOOLEAN,
        self::TYPE_JSON,
        self::TYPE_EMAIL,
        self::TYPE_URL,
        self::TYPE_IMAGE,
    ];

    protected $table = 'settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'is_public',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    public function scopeByKey($query, string $key)
    {
        return $query->where('key', $key);
    }

    /**
     * Trả về value đã parse theo type
     */
    public function getParsedValue()
    {
        return match ($this->type) {
            'boolean' => (bool) $this->value,
            'integer' => (int) $this->value,
            'float' => (float) $this->value,
            'number' => (float) $this->value,
            'json' => json_decode($this->value, true),
            default => $this->value,
        };
    }

    /**
     * Lấy nhanh setting theo key
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();

        return $setting ? $setting->getParsedValue() : $default;
    }

    protected static function booted()
    {
        static::saved(function () {
            Cache::forget('settings');
        });

        static::deleted(function () {
            Cache::forget('settings');
        });
    }
}
