<?php

namespace App\Models;

use App\Models\Concerns\HasImageIds;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Product extends Model
{
    use HasFactory;
    use HasImageIds;

    protected $table = 'products';

    protected $fillable = [
        'sku',
        'name',
        'slug',
        'description',
        'short_description',
        'price',
        'sale_price',
        'cost_price',
        'stock_quantity',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'meta_canonical',
        'primary_category_id',
        'category_included_ids',
        'category_ids',
        'tag_ids',
        'image_ids',
        'is_featured',
        'locked_by',
        'locked_at',
        'created_by',
        'is_active',
        'category_ids_backup',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'category_ids' => 'array',
        'tag_ids' => 'array',
        'image_ids' => 'array',
        'category_ids_backup' => 'array',
        'meta_keywords' => 'array',
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'locked_at' => 'datetime',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'category_included_ids' => 'array',
    ];

    protected array $reviewDisplayCache = [];

    public function slugHistories()
    {
        return $this->hasMany(ProductSlugHistory::class);
    }

    public function creator()
    {
        return $this->belongsTo(Account::class, 'created_by');
    }

    public function lockedByUser()
    {
        return $this->belongsTo(Account::class, 'locked_by');
    }

    public function primaryCategory()
    {
        return $this->belongsTo(Category::class, 'primary_category_id');
    }

    /**
     * Get tags from tag_ids array (not using entity_id)
     */
    public function getTagsAttribute()
    {
        $tagIds = $this->attributes['tag_ids'] ?? null;
        if (empty($tagIds)) {
            return new EloquentCollection;
        }

        $ids = is_array($tagIds) ? $tagIds : json_decode($tagIds, true) ?? [];
        if (empty($ids)) {
            return new EloquentCollection;
        }

        return Tag::whereIn('id', $ids)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Relationship với ProductVariant
     */
    public function variants()
    {
        return $this->hasMany(ProductVariant::class)->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Tất cả variants (kể cả inactive)
     */
    public function allVariants()
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    /**
     * Kiểm tra sản phẩm có variants không
     */
    public function hasVariants(): bool
    {
        return $this->variants()->exists();
    }

    /**
     * Lấy giá thấp nhất từ variants
     */
    public function getMinVariantPriceAttribute(): ?float
    {
        if (! $this->hasVariants()) {
            return null;
        }

        $variants = $this->variants()->get();
        if ($variants->isEmpty()) {
            return null;
        }

        $prices = $variants->map(function ($variant) {
            return (float) ($variant->sale_price ?? $variant->price);
        });

        return $prices->min();
    }

    /**
     * Lấy giá cao nhất từ variants
     */
    public function getMaxVariantPriceAttribute(): ?float
    {
        if (! $this->hasVariants()) {
            return null;
        }

        $variants = $this->variants()->get();
        if ($variants->isEmpty()) {
            return null;
        }

        $prices = $variants->map(function ($variant) {
            return (float) ($variant->sale_price ?? $variant->price);
        });

        return $prices->max();
    }

    /**
     * Lấy tổng số lượng tồn kho từ tất cả variants
     */
    public function getTotalVariantStockAttribute(): ?int
    {
        if (! $this->hasVariants()) {
            return null;
        }

        $variants = $this->variants()->get();

        // Nếu có variant không giới hạn tồn kho (null) thì trả về null
        if ($variants->contains(fn ($v) => $v->stock_quantity === null)) {
            return null;
        }

        return $variants->sum('stock_quantity');
    }

    public function scopeInCategory($query, array $categoryIds)
    {
        return $query->where(function ($q) use ($categoryIds) {
            $q->whereIn('primary_category_id', $categoryIds)
                ->orWhere(function ($q2) use ($categoryIds) {
                    foreach ($categoryIds as $id) {
                        // Kiểm tra cả integer và string vì JSON có thể lưu dưới cả hai dạng
                        $q2->orWhereJsonContains('category_ids', (int) $id)
                            ->orWhereJsonContains('category_ids', (string) $id);
                    }
                });
        });
    }

    public function getFrameAttribute()
    {
        // Logic để lấy frame, ví dụ:
        if ($this->is_featured) {
            return 'frame-free-ship-hot.png';
        }
        if ($this->sale_price && $this->sale_price < $this->price) {
            return 'frame-price-sale.png';
        }

        return 'frame-free-ship-hot.png';
    }

    public function getLabelAttribute()
    {
        if ($this->is_featured) {
            return 'Nổi bật';
        }
        if ($this->sale_price && $this->sale_price < $this->price) {
            return 'Giảm giá';
        }

        return 'Bán chạy '.date('Y').'';
    }

    public function faqs()
    {
        return $this->hasMany(ProductFaq::class);
    }

    public function howTos()
    {
        return $this->hasMany(ProductHowTo::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function scopeWithApprovedCommentsMeta($query)
    {
        return $query
            ->withCount(['comments as approved_comments_count' => function ($q) {
                $q->where('is_approved', true)->whereNull('parent_id');
            }])
            ->withAvg(['comments as approved_rating_avg' => function ($q) {
                $q->where('is_approved', true)
                    ->whereNull('parent_id')
                    ->whereNotNull('rating');
            }], 'rating');
    }

    public function getHasRealReviewsAttribute(): bool
    {
        return ($this->approved_comments_count ?? 0) > 0;
    }

    public function getDisplayReviewCountAttribute(): int
    {
        if ($this->has_real_reviews) {
            return (int) $this->approved_comments_count;
        }

        if (! array_key_exists('review_count', $this->reviewDisplayCache)) {
            $this->reviewDisplayCache['review_count'] = rand(10, 1000);
        }

        return $this->reviewDisplayCache['review_count'];
    }

    public function getDisplayRatingValueAttribute(): float
    {
        if ($this->has_real_reviews && ($this->approved_rating_avg ?? null)) {
            $avg = max(1, min(5, (float) $this->approved_rating_avg));

            return round($avg, 1);
        }

        if (! array_key_exists('rating_value', $this->reviewDisplayCache)) {
            $this->reviewDisplayCache['rating_value'] = rand(40, 50) / 10;
        }

        return $this->reviewDisplayCache['rating_value'];
    }

    public function getDisplayRatingStarAttribute(): int
    {
        if (! array_key_exists('rating_star', $this->reviewDisplayCache)) {
            $this->reviewDisplayCache['rating_star'] = (int) round($this->display_rating_value);
        }

        return $this->reviewDisplayCache['rating_star'];
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * Scope only active products.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function scopeNew($query)
    {
        $thirtyDaysAgo = now()->subDays(30);

        return $query->active()->where('created_at', '>=', $thirtyDaysAgo);
    }

    /**
     * Scope featured products.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', 1);
    }

    public static function getRelatedProducts(self $product, int $limit = 10)
    {
        $currentId = $product->id;
        $half = intdiv($limit, 2);

        $baseQuery = static::query()
            ->active()
            ->withApprovedCommentsMeta()
            ->where('id', '!=', $currentId)
            ->where(function ($q) use ($product) {
                $q->where('primary_category_id', $product->primary_category_id)
                    ->orWhereJsonContains('category_ids', (int) $product->primary_category_id)
                    ->orWhereJsonContains('category_ids', (string) $product->primary_category_id);
            });

        // 1️⃣ Lấy các sản phẩm trước
        $before = (clone $baseQuery)
            ->where('id', '<', $currentId)
            ->orderByDesc('id')
            ->limit($half)
            ->get();

        // 2️⃣ Lấy các sản phẩm sau
        $after = (clone $baseQuery)
            ->where('id', '>', $currentId)
            ->orderBy('id')
            ->limit($half)
            ->get();

        // 3️⃣ Fallback nếu thiếu ở phía trước: lấy thêm ở phía sau
        if ($before->count() < $half) {
            $need = $half - $before->count();

            $extraAfter = (clone $baseQuery)
                ->where('id', '>', optional($after->last())->id ?? $currentId)
                ->orderBy('id')
                ->limit($need)
                ->get();

            $after = $after->merge($extraAfter);
        }

        // 4️⃣ Fallback nếu thiếu ở phía sau: lấy thêm ở phía trước
        if ($after->count() < $half) {
            $need = $half - $after->count();

            $extraBefore = (clone $baseQuery)
                ->where('id', '<', optional($before->first())->id ?? $currentId)
                ->orderByDesc('id')
                ->limit($need)
                ->get();

            $before = $extraBefore->merge($before);
        }

        // 5️⃣ Sắp xếp: trước (id tăng dần) rồi tới sau
        $collection = $before
            ->reverse()
            ->merge($after)
            ->take($limit)
            ->values();

        static::preloadImages($collection);

        return $collection;
    }

    public function extraCategories()
    {
        return Category::whereIn('id', $this->category_ids ?? [])->get();
    }

    // Quan hệ tới flash_sale_items
    public function flashSaleItems()
    {
        return $this->hasMany(FlashSaleItem::class, 'product_id');
    }

    // Quan hệ gián tiếp tới FlashSale
    public function flashSales()
    {
        return $this->belongsToMany(FlashSale::class, 'flash_sale_items', 'product_id', 'flash_sale_id')->withPivot(['original_price', 'sale_price', 'stock', 'sold', 'max_per_user', 'is_active']);
    }

    // Flash Sale hiện tại (nếu có)
    public function currentFlashSale()
    {
        return $this->belongsToMany(FlashSale::class, 'flash_sale_items', 'product_id', 'flash_sale_id')
            ->withPivot(['original_price', 'sale_price', 'stock', 'sold', 'max_per_user', 'is_active'])
            ->where('flash_sales.is_active', 1)
            ->where('flash_sales.status', 'active')
            ->whereRaw('flash_sales.start_time <= NOW()')
            ->whereRaw('flash_sales.end_time >= NOW()')
            ->where('flash_sale_items.is_active', 1)
            ->limit(1);
    }

    /**
     * Kiểm tra có Flash Sale hiện tại không (dùng cùng điều kiện với currentFlashSale)
     */
    public function hasCurrentFlashSale(): bool
    {
        return $this->currentFlashSale()->exists();
    }

    // Flash Sale Item hiện tại (nếu có)
    public function currentFlashSaleItem()
    {
        return $this->hasOne(FlashSaleItem::class, 'product_id')
            ->whereHas('flashSale', function ($query) {
                $query->where('is_active', 1)->where('status', 'active')->where('start_time', '<=', now())->where('end_time', '>=', now());
            })
            ->where('is_active', 1)
            ->latest('id');
    }

    // Kiểm tra sản phẩm có đang trong flash sale không
    public function isInFlashSale(): bool
    {
        return $this->flashSaleItems()
            ->where('is_active', 1)
            ->whereHas('flashSale', function ($q) {
                $q->where('is_active', 1)->whereRaw('start_time <= NOW()')->whereRaw('end_time >= NOW()');
            })
            ->exists();
    }

    // Lấy giá flash sale hiện tại
    public function getFlashSalePriceAttribute()
    {
        $flashSaleItem = $this->currentFlashSaleItem;

        return $flashSaleItem ? $flashSaleItem->sale_price : null;
    }

    // Lấy giá gốc trong flash sale
    public function getFlashSaleOriginalPriceAttribute()
    {
        $flashSaleItem = $this->currentFlashSaleItem;

        return $flashSaleItem ? $flashSaleItem->original_price : null;
    }

    public function resolveCartPrice(): float
    {
        $flashSaleItem = $this->currentFlashSaleItem ?? $this->currentFlashSaleItem()->first();

        if ($flashSaleItem && $flashSaleItem->sale_price !== null) {
            return (float) $flashSaleItem->sale_price;
        }

        if ($this->sale_price && $this->sale_price > 0 && $this->sale_price < $this->price) {
            return (float) $this->sale_price;
        }

        return (float) $this->price;
    }

    public function flashSaleLimitPerUser(): ?int
    {
        $flashSaleItem = $this->currentFlashSaleItem ?? $this->currentFlashSaleItem()->first();

        return $flashSaleItem?->max_per_user;
    }

    // Lấy thông tin flash sale hiện tại
    public function getCurrentFlashSaleInfoAttribute()
    {
        $flashSaleItem = $this->currentFlashSaleItem;
        if (! $flashSaleItem) {
            return null;
        }

        return [
            'flash_sale' => $flashSaleItem->flashSale,
            'sale_price' => $flashSaleItem->sale_price,
            'original_price' => $flashSaleItem->original_price,
            'stock' => $flashSaleItem->stock,
            'sold' => $flashSaleItem->sold,
            'remaining' => $flashSaleItem->stock - $flashSaleItem->sold,
            'max_per_user' => $flashSaleItem->max_per_user,
            'discount_percent' => $flashSaleItem->original_price > 0 ? round((($flashSaleItem->original_price - $flashSaleItem->sale_price) / $flashSaleItem->original_price) * 100) : 0,
        ];
    }

    // Scope: Sản phẩm đang trong flash sale
    public function scopeInFlashSale($query)
    {
        return $query->whereHas('currentFlashSaleItem');
    }

    // Scope: Sản phẩm có flash sale sắp tới
    public function scopeUpcomingFlashSale($query)
    {
        return $query->whereHas('flashSaleItems.flashSale', function ($q) {
            $q->where('is_active', 1)->where('status', 'active')->where('start_time', '>', now());
        });
    }

    public function newCollection(array $models = []): EloquentCollection
    {
        $collection = new EloquentCollection($models);
        static::preloadImages($collection);

        return $collection;
    }

    protected static function booted(): void
    {
        static::saved(function () {
            app(\App\Services\SitemapService::class)->clearCache();
        });

        static::deleted(function () {
            app(\App\Services\SitemapService::class)->clearCache();
        });

        static::updating(function (self $product) {
            if ($product->isDirty('slug')) {
                $oldSlug = $product->getOriginal('slug');
                if ($oldSlug) {
                    ProductSlugHistory::firstOrCreate(
                        ['slug' => $oldSlug],
                        ['product_id' => $product->id]
                    );
                    Cache::forget('product_detail_'.$oldSlug);
                }
            }
        });
    }
}

class ProductSlugHistory extends Model
{
    protected $table = 'product_slug_histories';

    protected $fillable = [
        'product_id',
        'slug',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
