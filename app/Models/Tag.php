<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Tag extends Model
{
    use HasFactory;

    // Constants for entity types
    public const ENTITY_PRODUCT = 'product';

    public const ENTITY_POST = 'post';

    public const ENTITY_TYPES = [
        self::ENTITY_PRODUCT => Product::class,
        self::ENTITY_POST => Post::class,
    ];

    protected $table = 'tags';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'usage_count',
        'entity_id',
        'entity_type',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'usage_count' => 'integer',
    ];

    // Relationships
    public function taggable(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'entity_type', 'entity_id');
    }

    /**
     * Alias for taggable() to match Controller usage
     */
    public function entity(): MorphTo
    {
        return $this->taggable();
    }

    // Scopes
    public function scopeFilter($query, array $filters = [])
    {
        if (! empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('slug', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        if (! empty($filters['entity_type'])) {
            // Normalize entity_type nếu cần
            $entityType = $filters['entity_type'];
            // Nếu là string 'product' hoặc 'post', normalize thành class name
            if ($entityType === 'product') {
                $entityType = self::normalizeEntityType(self::ENTITY_PRODUCT);
            } elseif ($entityType === 'post') {
                $entityType = self::normalizeEntityType(self::ENTITY_POST);
            } elseif (in_array($entityType, [self::ENTITY_PRODUCT, self::ENTITY_POST], true)) {
                $entityType = self::normalizeEntityType($entityType);
            }
            $query->where('entity_type', $entityType);
        }

        // Filter by entity_id (specific product/post)
        if (! empty($filters['entity_id'])) {
            $query->where('entity_id', (int) $filters['entity_id']);
        }

        // Filter by status (active/inactive)
        if (! empty($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->where('is_active', true);
            } elseif ($filters['status'] === 'inactive') {
                $query->where('is_active', false);
            }
        } elseif (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        // Filter by usage_count
        if (! empty($filters['usage_count_min'])) {
            $query->where('usage_count', '>=', (int) $filters['usage_count_min']);
        }

        if (! empty($filters['usage_count_max'])) {
            $query->where('usage_count', '<=', (int) $filters['usage_count_max']);
        }

        // Filter by created_at
        if (! empty($filters['created_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }

        if (! empty($filters['created_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_to']);
        }

        return $query;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Accessors
    public function getEntityAttribute()
    {
        return $this->taggable;
    }

    public function getEntityTypeLabelAttribute(): string
    {
        $type = $this->denormalizeEntityType($this->entity_type);

        return match ($type) {
            self::ENTITY_PRODUCT => 'Sản phẩm',
            self::ENTITY_POST => 'Bài viết',
            default => class_basename($this->entity_type),
        };
    }

    public function getEntityNameAttribute(): ?string
    {
        if (! $this->taggable) {
            return null;
        }

        if ($this->taggable instanceof Product) {
            return $this->taggable->name ?? null;
        }

        if ($this->taggable instanceof Post) {
            return $this->taggable->title ?? null;
        }

        return null;
    }

    public function getEntityUrlAttribute(): ?string
    {
        if (! $this->taggable) {
            return null;
        }

        if ($this->taggable instanceof Product) {
            return route('admin.products.edit', $this->taggable);
        }

        if ($this->taggable instanceof Post) {
            return route('admin.posts.edit', $this->taggable);
        }

        return null;
    }

    public function getStatusBadgeAttribute(): string
    {
        if ($this->is_active) {
            return '<span class="badge badge-success">Active</span>';
        }

        return '<span class="badge badge-secondary">Inactive</span>';
    }

    // Helper methods
    public static function normalizeEntityType(string $type): string
    {
        return self::ENTITY_TYPES[$type] ?? $type;
    }

    public static function denormalizeEntityType(string $type): string
    {
        $flipped = array_flip(self::ENTITY_TYPES);

        return $flipped[$type] ?? $type;
    }
}
