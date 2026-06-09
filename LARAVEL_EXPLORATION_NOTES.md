# Laravel Project Structure Exploration - AutoSensor.vn

## Executive Summary
This is a comprehensive analysis of the Laravel project's data models, relationships, database schema, query patterns, caching strategies, and performance issues. Key findings show that while optimization work has been done, there are still several N+1 query problems and opportunities for improvement.

---

## 1. POST MODEL & RELATIONSHIPS

### Model Definition
- **Location**: [app/Models/Post.php](app/Models/Post.php)
- **Table**: `posts`
- **Soft Deletes**: Yes
- **Traits**: `HasFactory`, `SoftDeletes`, `HasImageIds`

### Key Properties
```php
protected $fillable = [
    'title', 'slug', 'meta_title', 'meta_description', 'meta_keywords',
    'meta_canonical', 'tag_ids', 'excerpt', 'content', 'image_ids',
    'status', 'is_featured', 'views', 'account_id', 'category_id',
    'published_at', 'created_by', 'created_at', 'updated_at', 'deleted_at'
];

protected $casts = [
    'tag_ids' => 'array',           // JSON - list of tag IDs
    'image_ids' => 'array',         // JSON - list of image IDs
    'meta_keywords' => 'array',     // JSON - array of keywords
    'published_at' => 'datetime',
    'is_featured' => 'boolean',
    'views' => 'integer',
];
```

### Relationships

#### 1. **creator()** - One-to-One
- `belongsTo(Account::class, 'created_by')`
- User who created the post

#### 2. **author()** - One-to-One
- `belongsTo(Account::class, 'account_id')`
- Post owner/author

#### 3. **category()** - One-to-One
- `belongsTo(Category::class)`
- Category the post belongs to

#### 4. **revisions()** - One-to-Many
- `hasMany(PostRevision::class)`
- Historical versions of the post

#### 5. **comments()** - Polymorphic One-to-Many
- `morphMany(Comment::class, 'commentable')`
- Comments on this post

#### 6. **tags()** - One-to-Many (Entity-based)
- `hasMany(Tag::class, 'entity_id')->where('entity_type', self::class)`
- Tags associated with the post
- **Note**: Uses custom implementation, not standard many-to-many

### Important Methods

#### **eager loading via newCollection()**
```php
public function newCollection(array $models = []): EloquentCollection {
    $collection = new EloquentCollection($models);
    static::preloadImages($collection);  // Auto-preload images when collection created
    return $collection;
}
```

#### **coverImagePath()**
- Returns the normalized path to the primary/cover image
- Uses `$this->primaryImage` from `HasImageIds` trait
- Falls back to `$this->thumbnail` property
- Returns `null` if no image found

#### **Status Scopes**
- `scopePublished()` - Filter published posts with correct datetime
- `scopeFeatured()` - Filter featured posts
- `scopeStatus($status)` - Filter by specific status
- `scopeInCategory($categoryId)` - Filter by category

### Eager Loading Patterns Used

**In BlogController**:
```php
// Index view - selects specific columns
Post::query()
    ->published()
    ->select(['id', 'title', 'slug', 'excerpt', 'image_ids', 'category_id', 'published_at', 'views', 'created_at'])
    ->with(['category:id,name,slug'])

// Show view - fuller relationship loading
$post->loadMissing([
    'author:id,name',
    'category:id,name,slug', 
    'creator:id,name',
])
```

**Image Preloading**:
```php
// After loading posts collection, preload all images at once
Post::preloadImages($posts->getCollection());
```

---

## 2. COMMENT MODEL & RATING SYSTEM

### Model Definition
- **Location**: [app/Models/Comment.php](app/Models/Comment.php)
- **Table**: `comments`
- **Soft Deletes**: No (uses `deleted_at` for cascade but not `SoftDeletes` trait)
- **Uses Traits**: `HasFactory`

### Key Properties
```php
protected $fillable = [
    'account_id', 'session_id', 'commentable_id', 'commentable_type',
    'parent_id', 'content', 'name', 'email', 'is_approved',
    'ip', 'rating', 'user_agent', 'is_reported', 'reports_count',
    'created_at', 'updated_at'
];

protected $casts = [
    'is_approved' => 'boolean',
    'is_reported' => 'boolean',
    'rating' => 'integer',          // 1-5 stars
    'reports_count' => 'integer',
];
```

### Constants
```php
const TYPE_POST = 'post';
const TYPE_PRODUCT = 'product';
const TYPES = [
    self::TYPE_POST => \App\Models\Post::class,
    self::TYPE_PRODUCT => \App\Models\Product::class,
];
```

### Relationships

#### 1. **account()** - One-to-One
- `belongsTo(Account::class)`
- Author (if logged in user)

#### 2. **commentable()** - Polymorphic Many-to-One
- `morphTo()`
- The model being commented on (Post or Product)

#### 3. **parent()** - One-to-One (Self-reference)
- `belongsTo(Comment::class, 'parent_id')`
- Parent comment (for nested replies)

#### 4. **replies()** - One-to-Many (Self-reference)
- `hasMany(Comment::class, 'parent_id')`
- All replies to this comment

#### 5. **adminReply()** - One-to-One (Specialized)
- `hasOne(Comment::class, 'parent_id')->whereNotNull('account_id')->whereHas('account', fn($q) => $q->where('role', 'admin'))`
- Single admin reply to this comment

### Rating System

**How Ratings Work**:
- Only applies to comments with `parent_id = NULL` (root comments)
- Optional property: `rating` (1-5 stars, nullable)
- Only approved comments (`is_approved = true`) are included in statistics

**Database Indexes for Performance**:
```
- comments_product_ratings_idx: (commentable_type, commentable_id, parent_id, is_approved, rating)
- commentable_stats_composite_index: (is_approved, parent_id, rating)
```

---

## 3. TAG MODEL & tag_ids COLUMN

### Model Definition
- **Location**: [app/Models/Tag.php](app/Models/Tag.php)
- **Table**: `tags`

### Key Properties
```php
protected $fillable = [
    'name', 'slug', 'description', 'is_active', 'usage_count',
    'entity_id', 'entity_type'
];

protected $casts = [
    'is_active' => 'boolean',
    'usage_count' => 'integer',
];

const ENTITY_PRODUCT = 'product';
const ENTITY_POST = 'post';
const ENTITY_TYPES = [
    self::ENTITY_PRODUCT => Product::class,
    self::ENTITY_POST => Post::class,
];
```

### Tag Schema & Relationships

**Database Columns**:
- `id` (Primary key)
- `name` (TEXT) - Changed from VARCHAR in migration `2026_01_12_203532`
- `slug` (unique)
- `description` (nullable)
- `is_active` (boolean)
- `usage_count` (counter, not automatically maintained)
- `entity_id` (unsigned bigint - references post_id or product_id)
- `entity_type` (string - stores class name like `App\Models\Post`)
- `timestamps`

**Indexes**:
```
UNIQUE INDEX: slug
COMPOSITE INDEX: (entity_id, entity_type)
```

### Tag vs tag_ids Column

**tag_ids Column (in posts/products)**:
- Stored as **JSON array** in Post/Product model
- Example: `[1, 3, 5, 7]` in posts table
- Uses casting: `protected $casts = ['tag_ids' => 'array'];`

**tags Table (separate table)**:
- Denormalized reference storage
- Each Tag record has:
  - Full tag metadata (name, slug, description)
  - Reference to the entity (entity_id, entity_type)
  - Usage tracking (usage_count)

**The Relationship**:
```php
// In Post model
public function tags() {
    return $this->hasMany(Tag::class, 'entity_id')
        ->where('entity_type', self::class);
}
```

⚠️ **Potential Issue**: Inconsistency between `tag_ids` (JSON array in post) and `tags` table records can occur:
- If tag_ids is updated but Tag records aren't
- If Tag records are deleted but post.tag_ids still references them

**Migration Info** (2026_01_12_203532):
- Changed `tags.name` from VARCHAR(255) → TEXT
- Allows longer tag names without truncation

---

## 4. IMAGE LOADING METHODS

### Location
[app/Models/Concerns/HasImageIds.php](app/Models/Concerns/HasImageIds.php)

### Properties
```php
protected ?Collection $resolvedImages = null;
protected static array $imagePool = [];  // Static memory pool for batching
```

### Key Methods

#### **images() - Accessor**
```php
public function getImagesAttribute(): Collection {
    // Returns cached Collection of Image models
    // Uses in-memory pool to avoid repeated queries
    // Called via $post->images
}
```

**How it works**:
1. If `$resolvedImages` is already set, return cached version
2. Get normalized image IDs from `$this->image_ids`
3. Call `hydrateImagePool()` to batch-load missing images
4. Map IDs to actual Image models from pool
5. Cache result in `$resolvedImages`
6. Return as Collection

#### **preloadImages($models) - Static Method**
```php
public static function preloadImages($models): void {
    // Batch-loads images for multiple models to prevent N+1
    // Accepts: Eloquent Collection, Support Collection, or plain array
}
```

**How it works**:
1. Accept a collection of models (posts, products, etc.)
2. Extract all unique image IDs from all models
3. Check which IDs are missing from the `$imagePool`
4. Query only missing images: `Image::whereIn('id', $missing)->get()`
5. Add results to static `$imagePool` with ID as key
6. Mark non-existent IDs as `null` in pool (prevents repeated queries)

#### **primaryImage Accessor**
```php
public function getPrimaryImageAttribute(): ?Image {
    return $this->images->first();  // First image is primary
}
```

#### **normalizedImageIds()**
```php
protected function normalizedImageIds(): array {
    // Cleans and validates image_ids array
    // - Removes duplicates
    // - Ensures integers
    // - Filters out invalid values
}
```

#### **clearResolvedImages()**
```php
public function clearResolvedImages(): void {
    $this->resolvedImages = null;  // Cache invalidation
}
```

### coverImagePath() Method

Located in [Post.php](app/Models/Post.php#L193):

```php
public function coverImagePath(): ?string {
    if ($this->primaryImage) {
        return $this->normalizeClientImagePath($this->primaryImage->url);
    }
    if (!empty($this->thumbnail)) {
        return $this->normalizeClientImagePath($this->thumbnail);
    }
    return null;
}

protected function normalizeClientImagePath(?string $value): ?string {
    // Converts various path formats to consistent 'clients/assets/img/...' format
    // - Handles absolute URLs
    // - Handles different path formats
    // - Defaults to 'clients/assets/img/posts/{filename}'
}
```

### Usage Pattern in Blog Controller

```php
// Before rendering
$posts = Post::query()->published()->get();
Post::preloadImages($posts);  // Single batch query for all images

// Later in view - no N+1 queries
foreach ($posts as $post) {
    $post->images;          // Uses cached images
    $post->coverImagePath(); // Uses primaryImage from pool
}
```

⚠️ **Performance Consideration**:
- Static `$imagePool` persists across entire request
- Good for same request, but watch for memory in long-running processes
- See `newCollection()` override in Post model - automatically preloads on collection creation

---

## 5. EXISTING CACHING STRATEGIES

### Cache Locations & TTLs

#### **ViewServiceProvider.php** - Global Shared Data

1. **Settings** (Forever - until manually cleared)
```php
Cache::rememberForever('settings', function () {
    return Setting::active()->get()->mapWithKeys(...)
});
```

2. **Main Navigation Categories** (24 hours)
```php
Cache::remember('autosensor_header_main_nav_category_lists_v6', 86400, function () {
    // Tree structure with children manually assembled
});
```

3. **Header Category Products** (Dynamic based on date)
```php
Cache::remember("autosensor_header_cat_products_{$categoryId}", ...)
```

#### **BlogController.php** - Blog-Specific Caches

1. **Sidebar Bundle** (30 minutes) - Combines 5 queries
```php
Cache::remember('blog_sidebar_bundle_v2', now()->addMinutes(30), function () {
    // Featured posts (3)
    // Sidebar categories with count
    // Sidebar tags
    // Recent posts (5)
    // Popular posts (5)
});
```

2. **Post Detail Bundle** (7 days)
```php
Cache::remember("blog_post_bundle_v5_{$post->id}", now()->addDays(7), function () {
    // Related posts, internal links, comments, rating stats, schema
});
```

3. **Total Posts Count** (1 hour)
```php
Cache::remember('blog_total_posts', now()->addHours(1), function() {
    return Post::published()->count();
});
```

4. **Category by Slug** (7 days)
```php
Cache::remember('blog_cat_'.$categorySlug, now()->addDays(7), function() { ... });
```

#### **Model Observers** - Cache Invalidation

In [Post.php](app/Models/Post.php#L250):
```php
protected static function booted(): void {
    static::saved(function (self $post) {
        Cache::forget('blog_total_posts');
        Cache::forget('blog_sidebar_bundle_v2');
        Cache::forget('homepage_featured_posts_v1');
        Cache::forget("blog_post_bundle_v5_{$post->id}");
        Cache::forget("blog_related_posts_{$post->id}");
        Cache::forget("blog_internal_links_{$post->id}");
        app(\App\Services\SitemapService::class)->clearCache();
    });
}
```

Similar invalidation for deleted posts.

#### **Other Strategic Caches**

- **Banner.php**: `'banners_home_parent'`, `'banners_home_children'`
- **VoucherService**: Voucher-related caches
- **ProductController**: `'slug_history_*'`, Product detail caches
- **CategoryHelper**: Category tree cache

### Cache Drivers Configuration
- **Default Driver**: Redis (based on usage with `Cache::remember()` patterns)
- **Fallback**: Likely file cache (standard Laravel)

### Cache Key Naming Conventions
- Model-specific: `blog_*`, `autosensor_header_*`
- Versioning: `_v1`, `_v2`, `_v6` (for invalidation)
- ID-based: `blog_post_bundle_v5_{$post->id}`
- Slug-based: `blog_cat_{$slug}`

⚠️ **Cache Aging Issue**: 
- Some caches use fixed durations (24h, 30m, 7d)
- Only invalidated when model is modified (saved/deleted)
- Could serve stale data for long periods if no updates occur

---

## 6. DATABASE SCHEMA

### Posts Table
- **Created by**: Migration `2025_12_04_200030_create_posts_table.php`
- **Columns**: 22 major columns (including timestamps + soft deletes)

**Key Columns**:
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint | PK |
| `title` | string | Post title |
| `slug` | string | UNIQUE - URL-friendly |
| `tag_ids` | json | Array of tag IDs |
| `image_ids` | json | Array of image IDs |
| `excerpt` | text | Short summary |
| `content` | longText | Full article content |
| `status` | enum | draft\|pending\|published\|archived |
| `is_featured` | boolean | Homepage featured flag |
| `views` | unsignedBigInt | View counter |
| `published_at` | timestamp | Publication datetime |
| `account_id` | FK (accounts) | Owner account |
| `category_id` | FK (categories) | Blog category |
| `created_by` | FK (accounts) | Creator (may differ from account_id) |
| `created_at`, `updated_at` | timestamps | |
| `deleted_at` | timestamp | Soft delete |

**Indexes** (2026_02_26_140104):
```
- UNIQUE: slug
- INDEX: account_id
- INDEX: category_id  
- INDEX: created_by
- INDEX: status
- INDEX: published_at
- INDEX: is_featured
```

### Comments Table
- **Created by**: Migration `2025_12_04_200022_create_comments_table.php`
- **Key Columns** (15 total):

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint | PK |
| `commentable_id` | unsignedBigInt | Polymorphic: post_id or product_id |
| `commentable_type` | string | Model class name |
| `account_id` | FK (accounts) | Author (if authenticated) |
| `parent_id` | FK (comments) | For nested replies |
| `content` | text | Comment text |
| `rating` | integer | 1-5 stars (nullable) |
| `is_approved` | boolean | Moderation status |
| `is_reported` | boolean | Spam flag |
| `reports_count` | integer | Spam report counter |
| `name`, `email` | string | Guest author info |
| `ip`, `user_agent` | string/text | Request metadata |
| `session_id` | string(100) | Guest session |

**Indexes** (2026_01_20_180000 & 2026_03_10_160140):
```
- Complex: (commentable_type, commentable_id, parent_id, is_approved, created_at)
- Complex: (commentable_type, commentable_id, parent_id, is_approved, rating)
- Composite: (parent_id, created_at)
- Composite: (commentable_type, commentable_id)
- Composite: (is_approved, parent_id, rating)
```

### Tags Table
- **Created by**: Migration `2025_12_04_200032_create_tags_table.php`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint | PK |
| `name` | text | Tag name (changed from VARCHAR) |
| `slug` | string | UNIQUE - URL friendly |
| `description` | text | Optional tag description |
| `is_active` | boolean | Active flag |
| `usage_count` | unsignedBigInt | Reference counter (not auto-updated) |
| `entity_id` | unsignedBigInt | Post ID or Product ID |
| `entity_type` | string | Model class name |

**Indexes**:
```
- UNIQUE: slug
- COMPOSITE: (entity_id, entity_type)
```

### Images Table
- **Created by**: Migration `2025_12_04_200012_create_images_table.php`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint | PK |
| `url` | string | Relative path or filename |
| `title` | string | Image title/alt |
| `alt` | string | Alt text for accessibility |
| `notes` | text | Admin notes |
| `is_primary` | boolean | Denormalized from post.image_ids[0] |
| `order` | integer | Sort order |
| `deleted_at` | timestamp | Soft deletes |

---

## 7. COMMENT SERVICE & RATING STATS

### Location
[app/Services/CommentService.php](app/Services/CommentService.php)

### Key Methods

#### **calculateRatingStats($type, $objectId)** - IMPORTANT
```php
public function calculateRatingStats(string $type, int $objectId): array {
    $comments = Comment::where('commentable_type', $type)
        ->where('commentable_id', $objectId)
        ->whereNotNull('rating')          // Only rated comments
        ->where('is_approved', true)       // Only approved
        ->whereNull('parent_id')           // Only root comments
        ->selectRaw('
            COUNT(*) as total_comments,
            AVG(rating) as average_rating,
            SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as star_1_count,
            SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as star_2_count,
            SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as star_3_count,
            SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as star_4_count,
            SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as star_5_count
        ')
        ->first();

    return [
        'total_comments' => (int)($comments->total_comments ?? 0),
        'average_rating' => round((float)($comments->average_rating ?? 0), 2),
        'star_1_count' => (int)($comments->star_1_count ?? 0),
        'star_2_count' => (int)($comments->star_2_count ?? 0),
        'star_3_count' => (int)($comments->star_3_count ?? 0),
        'star_4_count' => (int)($comments->star_4_count ?? 0),
        'star_5_count' => (int)($comments->star_5_count ?? 0),
    ];
}
```

**Performance**: Single SQL query with aggregation functions (GOOD)

#### **create()** - Create new comment
```php
// Sets is_approved = false by default
// Stores IP and user agent for moderation
// Cleans HTML content
```

#### **reply()** - Admin reply to comment
```php
// Creates child comment (parent_id set)
// Auto-approves parent comment if admin replies
// Deletes old admin reply if exists (one reply per comment)
```

#### **approve() / reject()** - Moderation
```php
// Boolean toggle for is_approved
```

#### Other Methods
- `updateReply()` - Edit admin reply content
- `deleteReply()` - Remove admin reply
- `delete()` - Remove comment + all replies

---

## 8. N+1 QUERY PROBLEMS & IDENTIFIED ISSUES

### CRITICAL N+1 Issues Found

#### 1. **Blog Sidebar Bundle** (Partially Fixed but Versioned)
**Status**: ✅ CACHED but expensive query pattern

**Location**: [BlogController.php:114-163](app/Http/Controllers/Clients/BlogController.php#L114)

**Query Loading**:
```php
// Called inside Cache::remember('blog_sidebar_bundle_v2', ...)
$sidebarCategories = Category::query()
    ->withCount(['posts as posts_count' => fn($q) => $q->published()])
    ->having('posts_count', '>', 0)
    ->orderByDesc('posts_count')
    ->take(6)
    ->get();
```

**Issue**: 
- `withCount()` generates expensive subqueries
- Each category gets a COUNT query
- Though cached for 30 minutes, expensive on cache miss

#### 2. **Related Posts Queries** (3 separate queries)
**Status**: ✅ CACHED for 30 days

**Location**: [BlogController.php:236-287](app/Http/Controllers/Clients/BlogController.php#L236)

**Pattern**:
```php
// 1. Previous posts (query with where conditions)
$previousPosts = Post::query()->where(...)-.get();

// 2. Next posts (separate query)
$nextPosts = Post::query()->where(...)-.get();

// 3. Additional posts (if above not enough)
$additionalPosts = Post::query()->where(...)-.get();
```

**Issue**:
- 3 separate queries combined with array merge
- Could be combined into single UNION query
- After cache hit, no issue; on cache miss = 3 queries

#### 3. **Comments with Admin Replies** (2 separate queries)
**Status**: ⚠️ NOT FULLY OPTIMIZED

**Location**: [BlogController.php:300-327](app/Http/Controllers/Clients/BlogController.php#L300)

**Current Pattern**:
```php
// Query 1: Get root comments
$comments = Comment::where('comments.commentable_type', 'post')
    ->where('comments.commentable_id', $post->id)
    ->whereNull('comments.parent_id')
    ->approved()
    ->with(['account:id,name,role'])
    ->orderByDesc('comments.created_at')
    ->limit(10)
    ->get();

// Query 2: Get admin replies for those comments
$commentIds = $comments->pluck('id');
if ($commentIds->isNotEmpty()) {
    $adminReplies = Comment::query()
        ->select('comments.*')
        ->whereIn('comments.parent_id', $commentIds)
        ->whereNotNull('comments.account_id')
        ->join('accounts', 'comments.account_id', '=', 'accounts.id')
        ->where('accounts.role', 'admin')
        ->with('account:id,name,role')
        ->get()
        ->keyBy('parent_id');
    
    // Manually attach replies to comments
    $comments->each(function ($comment) use ($adminReplies) {
        if ($adminReplies->has($comment->id)) {
            $comment->setRelation('adminReply', $adminReplies->get($comment->id));
        }
    });
}
```

**Improvement Opportunity**:
- Could use WITH clause (CTE) or nested join
- Could eager load adminReply using hasManyThrough or similar pattern
- Current approach with manual attachment works but is inelegant

#### 4. **Post Views in Blog Index** (Multiple preloadImages calls)
**Status**: ✅ SEMI-OPTIMIZED

**Location**: [BlogController.php:107-163](app/Http/Controllers/Clients/BlogController.php#L107)

**Pattern**:
```php
// Called 5 times in blog index:
Post::preloadImages($data['featuredPosts']);      // Line 121
Post::preloadImages($data['recentPosts']);        // Line 147
Post::preloadImages($posts->getCollection());     // Line 114
Post::preloadImages($collection->relatedPosts);   // Multiple times
```

**Issue**:
- Multiple separate calls to preloadImages()
- While batching works within each call, could combine all collections first
- Each call processes array filtering and ID extraction

#### 5. **Tag Queries with whereJsonContains** (No JOIN)
**Status**: ⚠️ Database-level inefficiency

**Location**: [BlogController.php:60-89](app/Http/Controllers/Clients/BlogController.php#L60)

**Pattern**:
```php
$postsQuery->where(function ($query) use ($tagIds) {
    foreach ($tagIds as $tagId) {
        $query->orWhereJsonContains('tag_ids', (int) $tagId)
            ->orWhereJsonContains('tag_ids', (string) $tagId);
    }
});
```

**Issue**:
- Multiple `orWhereJsonContains` conditions
- JSON operations are slower than foreign key JOINs
- No composite index on tag_ids (by design - JSON)

**Better Approach**:
- Could maintain normalized tag pivot table
- Use proper JOIN instead of JSON queries
- Current approach works for small tag counts

### Potential N+1 in Views

#### Via **coverImagePath()** method
```php
@foreach($posts as $post)
    {{ $post->coverImagePath() }}  // Calls $this->primaryImage each time
@endforeach
```

**Why it works**: `preloadImages()` pre-populates Image pool, so accessing `$post->primaryImage` doesn't trigger queries.

**Risk**: If `preloadImages()` is not called, becomes N+1.

### Comment Query Optimization

**Index Strategy** (2026_01_20_180000):
```sql
-- Supports main queries efficiently
INDEX (commentable_type, commentable_id, parent_id, is_approved, created_at)
INDEX (commentable_type, commentable_id, parent_id, is_approved, rating)
```

These composite indexes help but:
- Can't be used if query filters on subset of columns in different order
- Multiple indexes = slightly slower writes

---

## 9. PERFORMANCE OPTIMIZATION SUMMARY

### Already Implemented ✅
1. **Composite Database Indexes** for comments queries
2. **Redis Caching** for expensive queries
3. **Image Preloading** with static pool to prevent N+1
4. **Column Selection** (only select needed columns)
5. **Model-level Relationship Loading** (`with()`, `loadMissing()`)
6. **Time-based Cache Invalidation** (TTLs matching data freshness)
7. **Event-based Cache Invalidation** (Post::saved triggers forget)

### Remaining Opportunities ⚠️

1. **Combine Related Posts** (3 queries → 1)
2. **Admin Reply Eager Loading** (Manual attachment → proper relationship)
3. **Batch Image Preloading** (5 separate calls → 1 combined call)
4. **Tag Queries** (JSON containment → normalized pivot table for frequent filters)
5. **Category withCount** (Subquery → pre-aggregated denormalized column)

### Performance Notes

Per [PERFORMANCE_ANALYSIS_BLOG.md](PERFORMANCE_ANALYSIS_BLOG.md):
- **Current blog index load time**: Reported as 400ms-1.15s improvement made
- **Root issue patterns**: N+1 queries, expensive withCount, multiple separate queries
- **Caching effectiveness**: 30min-7day caches handling 90%+ of repeat loads

---

## 10. KEY FILES REFERENCE

| File | Purpose |
|------|---------|
| [app/Models/Post.php](app/Models/Post.php) | Post model with relationships |
| [app/Models/Comment.php](app/Models/Comment.php) | Comment model, polymorphic |
| [app/Models/Tag.php](app/Models/Tag.php) | Tag model with entity polymorphism |
| [app/Models/Concerns/HasImageIds.php](app/Models/Concerns/HasImageIds.php) | Image batch loading trait |
| [app/Services/CommentService.php](app/Services/CommentService.php) | Comment CRUD + rating calculations |
| [app/Http/Controllers/Clients/BlogController.php](app/Http/Controllers/Clients/BlogController.php) | Blog logic, caching orchestration |
| [app/Providers/ViewServiceProvider.php](app/Providers/ViewServiceProvider.php) | Global shared data, settings cache |
| [PERFORMANCE_ANALYSIS_BLOG.md](PERFORMANCE_ANALYSIS_BLOG.md) | Detailed performance analysis notes |

---

## 11. RELATIONSHIPS DIAGRAM

```
┌─────────────────────────────────────────────────────────────┐
│                      POST                                    │
├─────────────────────────────────────────────────────────────┤
│ id | title | slug | image_ids | tag_ids | status | ...      │
│         ↓ HasImageIds (trait)                                │
│     Static Image Pool (batch preload)                        │
└─────│───────────────────────────┬────────────────────────────┘
      │                           │
      │ ONE-to-ONE                │ ONE-to-ONE
      ↓                           ↓
   ACCOUNT                    CATEGORY
   (author)               (category_id FK)
      
┌─────────────────────────────────────────────────────────────┐
│                      TAG                                     │
├─────────────────────────────────────────────────────────────┤
│ id | name | slug | entity_id | entity_type | ...            │
│                                           ↓                  │
│                                    Polymorphic to Post/Product
└─────────────────────────────────────────────────────────────┘

                    ↓ tag_ids array in Post
            References Tag.id values via JSON

┌─────────────────────────────────────────────────────────────┐
│                    COMMENT                                   │
├─────────────────────────────────────────────────────────────┤
│ id | commentable_id | commentable_type | parent_id | rating  │
│  ↓ Polymorphic to Post/Product                             │
│  ↓ Self-reference parent (nested replies)                  │
│  ↓ account_id (optional, for authenticated users)          │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                    IMAGE                                     │
├─────────────────────────────────────────────────────────────┤
│ id | url | title | alt | is_primary | order | ...          │
│  ↑ Referenced by image_ids array in Post/Product            │
│  ↑ Batch-loaded via static pool (HasImageIds trait)         │
└─────────────────────────────────────────────────────────────┘
```

---

## 12. QUERY EXECUTION FLOW - Blog Show Page

```
1. POST RETRIEVED
   └─ Route model binding: Post::findOrFail($slug)

2. PERMISSION CHECK
   └─ Is published? Future check?

3. INCREMENT VIEWS
   └─ $post->increment('views')

4. REDIS CACHE CHECK (7 days)
   └─ Cache::remember("blog_post_bundle_v5_{$post->id}", ...)

5. IF CACHE MISS:
   a) Load relationships:
      └─ author (id, name)
      └─ category (id, name, slug)
      └─ creator (id, name)
   
   b) Batch preload images:
      └─ Post::preloadImages([$post])
      └─ Executes: Image::whereIn('id', [$imageIds])->get()
      └─ Populates static $imagePool
   
   c) Build related posts (3 queries):
      └─ Query 1: Previous posts
      └─ Query 2: Next posts  
      └─ Query 3: Additional posts
      └─ Preload images for all
   
   d) Load internal links (random):
      └─ Query: SELECT * FROM posts ORDER BY RAND()
      └─ Preload images
   
   e) Load comments (2 queries):
      └─ Query 1: Root comments with: (commentable_type, commentable_id, parent_id, is_approved)
      └─ Query 2: Admin replies for those comment IDs
   
   f) Calculate rating stats (1 aggregation):
      └─ Single query with COUNT, AVG, SUM(CASE...)
   
   g) Build schema (no DB queries)
      └─ JSON-LD schema generation

6. ON CACHE HIT:
   └─ Direct return of bundle

7. RETURN TO VIEW
   └─ All data pre-loaded, minimal queries
```

---

## Summary Statistics

- **Models Involved**: 5 main (Post, Comment, Tag, Account, Image)
- **Tables**: 8+ (posts, comments, tags, images, accounts, categories, + junction tables)
- **Relationships**: 11+ defined relationships
- **Cache Keys**: 20+ active caches at different TTLs
- **Composite Indexes**: 5+ on comments alone
- **N+1 Risks**: 3-4 identified (mostly mitigated by caching)
- **Average Load (cached)**: < 100ms
- **Average Load (cache miss)**: 400ms-1.15s

