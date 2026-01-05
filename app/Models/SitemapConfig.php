<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class SitemapConfig extends Model
{
    use HasFactory;

    protected $table = 'sitemap_configs';

    protected $fillable = [
        'config_key',
        'config_value',
        'value_type',
    ];

    protected $casts = [
        'config_value' => 'string',
    ];

    /**
     * Get the value for a given key with optional default.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $config = static::where('config_key', $key)->first();

        if (! $config) {
            return $default;
        }

        return $config->castValue();
    }

    /**
     * Persist a key-value pair.
     */
    public static function setValue(string $key, mixed $value, ?string $type = null): void
    {
        $type = $type ?: static::guessType($value);

        static::updateOrCreate(
            ['config_key' => $key],
            [
                'config_value' => static::prepareValue($value, $type),
                'value_type' => $type,
            ]
        );
    }

    /**
     * Cast the stored value to the expected PHP type.
     */
    protected function castValue(): mixed
    {
        return match ($this->value_type) {
            'boolean' => filter_var($this->config_value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->config_value,
            'float' => (float) $this->config_value,
            'datetime' => $this->config_value ? Carbon::parse($this->config_value) : null,
            'json' => $this->config_value ? json_decode($this->config_value, true) : null,
            default => $this->config_value,
        };
    }

    /**
     * Prepare the value for storage based on its type.
     */
    protected static function prepareValue(mixed $value, string $type): ?string
    {
        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'integer', 'float' => (string) $value,
            'datetime' => $value ? Carbon::parse($value)->toDateTimeString() : null,
            'json' => $value ? json_encode($value) : null,
            default => $value,
        };
    }

    /**
     * Guess the data type based on the value.
     */
    protected static function guessType(mixed $value): string
    {
        return match (true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_float($value) => 'float',
            $value instanceof Carbon => 'datetime',
            is_array($value) => 'json',
            default => 'string',
        };
    }
}
