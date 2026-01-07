## Hệ thống `slug_index` & resolve slug cho product / post / category

Tài liệu này mô tả toàn bộ cơ chế mới để xử lý slug cho **sản phẩm**, **bài viết**, **danh mục**, giúp:

- Ưu tiên đúng thứ tự: **product → post → category → fallback khác**.
- Resolve slug trong **một lần tra cứu** (không check tuần tự nhiều query).
- Dễ mở rộng thêm loại slug khác (brand, page, tag, ...).

---

### 1. Bảng `slug_indexes`

**Migration**: `database/migrations/2026_01_07_120000_create_slug_indexes_table.php`

- Bảng: `slug_indexes`
- Cấu trúc:
  - `id` (bigint, PK)
  - `slug` (`string`, **unique**): slug gốc.
  - `type` (`string`, index): loại entity (`product`, `post`, `category`, ...).
  - `entity_id` (`unsignedBigInteger`, index): ID của entity tương ứng.
  - `is_active` (`boolean`, index): active hay không (ẩn / unpublish sẽ set false).
  - `target_slug` (`string`, nullable): dùng cho redirect 301 nếu slug này chuyển sang slug mới.
  - `created_at`, `updated_at`

**Model**: `App\Models\SlugIndex`

```startLine:endLine:app/Models/SlugIndex.php
class SlugIndex extends Model
{
    use HasFactory;

    // Bảng dùng tên 'slug_indexes'
    protected $table = 'slug_indexes';

    protected $fillable = [
        'slug',
        'type',
        'entity_id',
        'is_active',
        'target_slug',
    ];
}
```

---

### 2. Đồng bộ slug tự động qua Observers

Các observer đảm bảo mỗi khi **Product / Post / Category** được tạo / cập nhật / xóa, bảng `slug_indexes` luôn khớp.

#### 2.1. Product: `ProductSlugObserver`

File: `app/Observers/ProductSlugObserver.php`

Chức năng:
- Lắng nghe sự kiện `saved` và `deleted` của `Product`.
- Gọi `SlugIndex::updateOrCreate(...)` với:
  - `type = 'product'`
  - `entity_id = $product->id`
  - `is_active = $product->is_active` (và không bị xóa).

#### 2.2. Post: `PostSlugObserver`

File: `app/Observers/PostSlugObserver.php`

Chức năng:
- Lắng nghe `saved` / `deleted` cho `Post`.
- `is_active` chỉ true nếu `status === 'published'`.
  - Bài viết nháp / pending sẽ không được resolve như post.

#### 2.3. Category: `CategorySlugObserver`

File: `app/Observers/CategorySlugObserver.php`

Chức năng:
- Lắng nghe `saved` / `deleted` cho `Category`.
- `is_active` gắn theo `$category->is_active`.

#### 2.4. Đăng ký observers

Thực hiện trong `App\Providers\AppServiceProvider::boot()`:

```startLine:endLine:app/Providers/AppServiceProvider.php
use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Observers\CategorySlugObserver;
use App\Observers\PostSlugObserver;
use App\Observers\ProductSlugObserver;

public function boot(): void
{
    // ...

    // Đăng ký observers để đồng bộ slug_index
    Product::observe(ProductSlugObserver::class);
    Post::observe(PostSlugObserver::class);
    Category::observe(CategorySlugObserver::class);
}
```

---

### 3. Lệnh artisan seed / rebuild bảng `slug_indexes`

**Command**: `app/Console/Commands/RebuildSlugIndex.php`

**Signature**:

```php
protected $signature = 'slug-index:rebuild {--force-clear : Xóa toàn bộ slug_indexes trước khi build lại}';
```

**Mục đích**:
- Chạy **một lần sau khi triển khai** để import toàn bộ slug cũ vào `slug_indexes`.
- Có thể chạy lại bất kỳ lúc nào nếu cần rebuild dữ liệu.

**Hành vi**:
- Nếu có `--force-clear`: `SlugIndex::truncate()` (xoá sạch, build lại từ đầu).
- Lần lượt đọc:
  - `Product` (id, slug, is_active) → `type = 'product'`.
  - `Post` (id, slug, status) → `type = 'post'`, `is_active = (status === 'published')`.
  - `Category` (id, slug, is_active) → `type = 'category'`.
- Dùng `chunkById(500, ...)` để tránh tốn RAM.
- Mỗi record gọi:

```php
SlugIndex::updateOrCreate(
    ['slug' => $model->slug],
    [
        'type' => 'product' | 'post' | 'category',
        'entity_id' => $model->id,
        'is_active' => ...,
        'target_slug' => null,
    ]
);
```

**Cách chạy**:

```bash
php artisan migrate                         # tạo bảng slug_indexes
php artisan slug-index:rebuild              # build lần đầu
# hoặc
php artisan slug-index:rebuild --force-clear
```

---

### 4. Resolve slug trong `ProductController::detail()`

File: `app/Http/Controllers/Clients/ProductController.php`

Mục tiêu:
- Ưu tiên: **product → post → category → fallback khác**.
- Resolve **trong 1 bước** qua `slug_indexes` + cache.
- Fallback an toàn bằng UNION ALL nếu `slug_indexes` chưa đủ dữ liệu.

#### 4.1. Cache + slug_index + fallback

Đoạn code chính:

```startLine:endLine:app/Http/Controllers/Clients/ProductController.php
        $cacheKey = 'slug_type_'.$slug;
        
        try {
            // Dùng slug_index để resolve 1 phát, không phải check tuần tự
            // Cache 1 giờ, fallback sang UNION ALL cũ nếu chưa có dữ liệu slug_index (đảm bảo không 404 oan)
            $slugType = Cache::remember($cacheKey, 3600, function () use ($slug) {
                $index = \App\Models\SlugIndex::where('slug', $slug)
                    ->where('is_active', true)
                    ->select('type', 'entity_id', 'target_slug')
                    ->first();

                if ($index) {
                    return [
                        'type' => $index->type,
                        'entity_id' => $index->entity_id,
                        'target_slug' => $index->target_slug,
                    ];
                }

                // Fallback: UNION ALL để không gián đoạn khi slug_index chưa được seed đầy đủ
                $sql = "
                    (
                        SELECT id, 'product' AS type, NULL AS target_slug
                        FROM products
                        WHERE slug = ? AND is_active = 1
                        LIMIT 1
                    )
                    UNION ALL
                    (
                        SELECT id, 'post' AS type, NULL AS target_slug
                        FROM posts
                        WHERE slug = ? AND status = 'published'
                        LIMIT 1
                    )
                    UNION ALL
                    (
                        SELECT id, 'category' AS type, NULL AS target_slug
                        FROM categories
                        WHERE slug = ? AND is_active = 1
                        LIMIT 1
                    )
                    LIMIT 1
                ";

                $result = DB::selectOne($sql, [$slug, $slug, $slug]);

                if (! $result) {
                    return ['type' => 'not_found', 'entity_id' => null, 'target_slug' => null];
                }

                return [
                    'type' => $result->type ?? 'not_found',
                    'entity_id' => $result->id ?? null,
                    'target_slug' => $result->target_slug ?? null,
                ];
            });
```

#### 4.2. Thứ tự ưu tiên (route logic)

Sau khi có `$slugType` (array):

- **Nếu là post**:

```startLine:endLine:app/Http/Controllers/Clients/ProductController.php
            // Nếu là bài viết, redirect sang route bài viết chuẩn
            if ($slugType['type'] === 'post') {
                // Không load thêm gì, chỉ điều hướng đúng URL bài viết
                return redirect()->route('client.blog.show', ['post' => $slug], 301);
            }
```

- **Nếu là category**:

```startLine:endLine:app/Http/Controllers/Clients/ProductController.php
            // Nếu là category, forward ngay đến ShopController (không query product)
            if ($slugType['type'] === 'category') {
                $shopController = app(ShopController::class);
                return $shopController->index(request(), $slug);
            }
```

- **Nếu `type === 'not_found'`**:
  - Chạy tiếp logic:
    - Kiểm tra `ProductSlugHistory` (slug cũ → redirect slug mới).
    - Thử parse `category-brand combo`.
    - Nếu không có gì khớp → 404.

- **Nếu là product**:
  - Giữ nguyên logic hiện tại: load product + cache `product_detail_{slug}`, preload images, vouchers, related products, comments, v.v.

---

### 5. Quy trình triển khai nhanh

1. **Migrate**:
   ```bash
   php artisan migrate
   ```
2. **Rebuild slug_indexes lần đầu**:
   ```bash
   php artisan slug-index:rebuild
   # hoặc, nếu muốn xoá sạch rồi build lại:
   php artisan slug-index:rebuild --force-clear
   ```
3. Sau đó, mọi thay đổi slug / publish / active trên:
   - Product
   - Post
   - Category  
   → đều được observers tự động sync vào `slug_indexes`.

4. Mọi request `/slug` sẽ:
   - Resolve qua `slug_indexes` + cache 1h.
   - Rơi về UNION ALL chỉ khi slug chưa có trong index (giai đoạn chuyển tiếp hoặc lỗi sync).

---

### 6. Mở rộng trong tương lai

- Muốn thêm loại slug mới (ví dụ `brand`, `page`):
  - Thêm observer tương ứng sync vào `slug_indexes` với `type = 'brand'` hoặc `page`.
  - Bổ sung xử lý trong `ProductController::detail()` (match thêm case `brand`/`page`).
- Muốn dùng redirect 301 slug cũ → slug mới:
  - Khi đổi slug, set `is_active = 0` cho slug cũ, `target_slug = slug_moi`.
  - Trong resolve, nếu tìm được bản ghi có `target_slug`, redirect 301 sang slug mới.


