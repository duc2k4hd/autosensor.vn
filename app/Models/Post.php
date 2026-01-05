<?php

namespace App\Models;

use App\Models\Concerns\HasImageIds;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory, SoftDeletes;
    use HasImageIds;

    protected $table = 'posts';

    protected $fillable = [
        'title',
        'slug',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'meta_canonical',
        'tag_ids',
        'excerpt',
        'content',
        'image_ids',
        'status',
        'is_featured',
        'views',
        'account_id',
        'category_id',
        'published_at',
        'created_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $appends = [
        'excerpt_text',
    ];

    protected $casts = [
        'tag_ids' => 'array',
        'image_ids' => 'array',
        'published_at' => 'datetime',
        'is_featured' => 'boolean',
        'views' => 'integer',
    ];

    public function creator()
    {
        return $this->belongsTo(Account::class, 'created_by');
    }

    public function author()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function revisions()
    {
        return $this->hasMany(PostRevision::class);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function tags()
    {
        return $this->hasMany(Tag::class, 'entity_id')
            ->where('entity_type', self::class);
    }

    /**
     * Scope published posts.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    /**
     * Scope featured posts.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope by category.
     */
    public function scopeInCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Publish the post now.
     */
    public function publish(): bool
    {
        $this->status = 'published';
        $this->published_at = $this->published_at ?: now();

        return $this->save();
    }

    /**
     * Unpublish the post.
     */
    public function unpublish(): bool
    {
        $this->status = 'draft';

        return $this->save();
    }

    /**
     * Check if post is published.
     */
    public function isPublished(): bool
    {
        return $this->status === 'published'
            && ($this->published_at === null || $this->published_at <= now());
    }

    /**
     * Check if post is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if post is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if post is archived.
     */
    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    public function newCollection(array $models = []): EloquentCollection
    {
        $collection = new EloquentCollection($models);
        static::preloadImages($collection);

        return $collection;
    }

    public function getExcerptTextAttribute(): string
    {
        $source = $this->excerpt ?? $this->content ?? '';
        $text = Str::of(strip_tags((string) $source))->squish()->toString();

        return Str::limit($text, 180) ?: '';
    }

    public function coverImagePath(): ?string
    {
        if ($this->primaryImage) {
            return $this->normalizeClientImagePath($this->primaryImage->url);
        }

        if (! empty($this->thumbnail)) {
            return $this->normalizeClientImagePath($this->thumbnail);
        }

        return null;
    }

    protected function normalizeClientImagePath(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $value = trim($value);

        if (Str::startsWith($value, ['http://', 'https://'])) {
            $value = Str::after($value, url('/'));
        }

        $value = ltrim($value, '/');

        if ($value === '') {
            return null;
        }

        // Nếu đã có path đầy đủ, giữ nguyên
        if (Str::startsWith($value, 'clients/assets/img/')) {
            return $value;
        }

        // Nếu có chứa dấu /, thêm prefix
        if (Str::contains($value, '/')) {
            return 'clients/assets/img/'.$value;
        }

        // Mặc định: lấy từ thư mục posts cho Post model
        return 'clients/assets/img/posts/'.$value;
    }

    protected static function booted(): void
    {
        static::saved(function (self $post) {
            app(\App\Services\SitemapService::class)->clearCache();

            Cache::forget('blog_related_posts_'.$post->id);
            Cache::forget('blog_internal_links_'.$post->id);
        });

        static::deleted(function (self $post) {
            app(\App\Services\SitemapService::class)->clearCache();

            Cache::forget('blog_related_posts_'.$post->id);
            Cache::forget('blog_internal_links_'.$post->id);
        });
    }
}
