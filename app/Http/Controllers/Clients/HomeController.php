<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\FlashSale;
use App\Models\Product;
use App\Models\Post;
use App\Models\Voucher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index()
    {
        $start = microtime(true);

        // ─── 1. Static Bundle ────────────────────────────────────────────────
        $staticBundle = Cache::remember('homepage_static_bundle_v3', now()->addDays(7), function () {
            return [
                'banners_home_parent' => Banner::active()
                    ->where('position', 'homepage_banner_parent')
                    ->select(['id', 'image_desktop', 'link', 'title', 'target', 'position'])
                    ->get(),
                'banners_home_children' => Banner::active()
                    ->where('position', 'homepage_banner_children')
                    ->select(['id', 'image_desktop', 'link', 'title', 'target', 'position'])
                    ->get(),
                'vouchers' => Voucher::active()
                    ->select(['id', 'name', 'image', 'description'])
                    ->limit(3)->get(),
                'partners' => Brand::where('is_active', true)
                    ->whereNotNull('image')->where('image', '!=', '')
                    ->select(['id', 'name', 'image', 'website', 'order'])
                    ->orderBy('order')->orderBy('name')->get(),
                'categoriesForTabs' => Category::active()
                    ->whereNull('parent_id')
                    ->select(['id', 'name', 'slug', 'image', 'order'])
                    ->orderBy('order')->orderBy('name')->take(5)->get(),
            ];
        });
        $checkpoint = microtime(true);

        $banners_home_parent   = $staticBundle['banners_home_parent'];
        $banners_home_children = $staticBundle['banners_home_children'];
        $vouchers              = $staticBundle['vouchers'];
        $partners              = $staticBundle['partners'];
        $categoriesForTabs     = $staticBundle['categoriesForTabs'];

        // ─── 2. Mega Bundle ──────────────────────────────────────────────────
        $megaStart = microtime(true);
        $cacheHit = true;
        $megaBundle = Cache::remember('homepage_mega_bundle_v14', now()->addDays(7), function () use ($categoriesForTabs, &$cacheHit) {
            $cacheHit = false;

            // A. Featured Products
            $featured = $this->fetchProductsRaw(
                'p.is_active = 1 AND p.is_featured = 1', [], 18
            );

            // B. Random Products (từ 100 latest)
            $latestIds = DB::table('products')
                ->where('is_active', true)->latest('id')->limit(100)
                ->pluck('id')->toArray();
            shuffle($latestIds);
            $randomIds = array_slice($latestIds, 0, 20);
            $random = empty($randomIds) ? [] : $this->fetchProductsRaw(
                'p.is_active = 1 AND p.id IN (' . implode(',', array_map('intval', $randomIds)) . ')',
                [], 20
            );

            // C. Category Tabs — Eloquent lấy ID (xử lý JSON đúng), sau đó raw SQL
            $tabs = [];
            foreach ($categoriesForTabs as $cat) {
                $catId  = (int) $cat->id;
                $tabIds = Product::active()->inCategory([$catId])
                    ->select('id')->limit(10)->pluck('id')->toArray();
                $tabs[$catId] = empty($tabIds) ? [] : $this->fetchProductsRaw(
                    'p.is_active = 1 AND p.id IN (' . implode(',', $tabIds) . ')',
                    [], 10
                );
            }

            // D. Category Product Counts
            $counts = DB::table('products')
                ->where('is_active', true)->whereNotNull('primary_category_id')
                ->groupBy('primary_category_id')
                ->select('primary_category_id', DB::raw('count(*) as aggregate'))
                ->pluck('aggregate', 'primary_category_id')
                ->map(fn($v) => (int)$v)->toArray();

            $extraRaw = DB::table('products')
                ->where('is_active', true)->whereNotNull('category_ids')
                ->where('category_ids', '!=', '[]')->pluck('category_ids');
            foreach ($extraRaw as $json) {
                $ids = json_decode($json, true);
                if (is_array($ids)) {
                    foreach ($ids as $id) {
                        $id = (int)$id;
                        $counts[$id] = ($counts[$id] ?? 0) + 1;
                    }
                }
            }

            return json_encode(['featured' => $featured, 'random' => $random, 'tabs' => $tabs, 'counts' => $counts], JSON_UNESCAPED_UNICODE);
        });
        
        $hydrateStart = microtime(true);

        $megaBundleArr = json_decode($megaBundle, true) ?? ['featured' => [], 'random' => [], 'tabs' => [], 'counts' => []];

        $productsFeatured      = $this->hydrateProducts($megaBundleArr['featured']);
        $productRandom         = $this->hydrateProducts($megaBundleArr['random']);
        $categoryProducts      = collect($megaBundleArr['tabs'])->map(fn($list) => $this->hydrateProducts($list));
        $categoryProductCounts = $megaBundleArr['counts'];

        $checkpoint = microtime(true);

        // ─── 4. Flash Sale ───────────────────────────────────────────────────
        $flashSale = Cache::remember('flash_sale_data_v7', 120, function () {
            $now = now();
            $flashSaleId = FlashSale::where('flash_sales.is_active', true)
                ->where('flash_sales.status', 'active')
                ->where('flash_sales.start_time', '<=', $now)
                ->where('flash_sales.end_time', '>=', $now)
                ->join('flash_sale_items', 'flash_sales.id', '=', 'flash_sale_items.flash_sale_id')
                ->where('flash_sale_items.is_active', true)
                ->whereRaw('flash_sale_items.stock > flash_sale_items.sold')
                ->value('flash_sales.id');

            if (!$flashSaleId) return null;

            $fsModel = FlashSale::with(['items.product.primaryCategory', 'items.product.variants'])->find($flashSaleId);
            if (!$fsModel) return null;

            $items = $fsModel->items
                ->filter(fn($i) => $i->product && $i->product->is_active)
                ->map(function ($item) {
                    $p   = $item->product;
                    $img = $p->primaryImage;
                    $imgUrl = $img?->url ?? 'no-image.webp';
                    $onSale = $p->sale_price && $p->sale_price < $p->price;
                    return [
                        'sale_price'     => $item->sale_price,
                        'original_price' => $item->original_price,
                        'stock'          => $item->stock,
                        'sold'           => $item->sold,
                        'product'        => [
                            'id'             => $p->id,
                            'name'           => $p->name,
                            'slug'           => $p->slug,
                            'sku'            => $p->sku,
                            'price'          => $p->price,
                            'sale_price'     => $p->sale_price,
                            'cart_price'     => $item->sale_price ?? $p->sale_price ?? $p->price,
                            'stock_quantity' => $p->stock_quantity,
                            'is_featured'    => $p->is_featured,
                            'primaryImage'   => ['url' => $imgUrl, 'alt' => $img?->alt ?? $p->name],
                            'primary_image'  => ['url' => $imgUrl, 'alt' => $img?->alt ?? $p->name],
                            'primaryCategory' => [
                                'name' => $p->primaryCategory?->name ?? 'Danh mục',
                                'slug' => $p->primaryCategory?->slug ?? '',
                            ],
                            'frame'   => $p->is_featured ? 'frame-free-ship-hot.png' : ($onSale ? 'frame-price-sale.png' : 'frame-free-ship-hot.png'),
                            'label'   => $p->is_featured ? 'Nổi bật' : ($onSale ? 'Giảm giá' : 'Bán chạy ' . date('Y')),
                            'approved_comments_count' => 0,
                            'approved_rating_avg'     => 5,
                            'display_rating_star'     => 5,
                            'display_review_count'    => 100,
                            'variants_data'           => $p->getVariantsData(),
                        ],
                    ];
                })->values()->toArray();

            return [
                'id'       => $fsModel->id,
                'name'     => $fsModel->name,
                'end_time' => $fsModel->end_time,
                'items'    => $items,
            ];
        });

        if ($flashSale) {
            $flashSale = (object)$flashSale;
            $flashSale->items = collect($flashSale->items)->map(function ($i) {
                $i = (object)$i;
                $p = (object)$i->product;
                $p->primaryImage    = (object)($p->primaryImage ?? []);
                $p->primary_image   = (object)($p->primary_image ?? []);
                $p->primaryCategory = (object)($p->primaryCategory ?? []);
                $i->product = $p;
                return $i;
            });
        }
        $checkpoint = microtime(true);

        // ─── 5. Featured Posts ───────────────────────────────────────────────
        $featuredPostsBundle = Cache::remember('homepage_featured_posts_v1', now()->addDays(7), function () {
            $posts = Post::query()
                ->published()
                ->where('is_featured', true)
                ->select(['id', 'title', 'slug', 'excerpt', 'image_ids', 'category_id', 'published_at', 'created_at'])
                ->with(['category:id,name,slug'])
                ->orderByDesc('published_at')
                ->orderByDesc('created_at')
                ->take(6)
                ->get();
            Post::preloadImages($posts);
            return $posts;
        });
        $featuredPosts = $featuredPostsBundle;
        $checkpoint = microtime(true);

        $totalTime = round((microtime(true) - $start) * 1000, 2);

        return view('clients.pages.home.index', compact(
            'banners_home_parent', 'banners_home_children', 'vouchers',
            'productsFeatured', 'productRandom', 'flashSale', 'partners',
            'categoriesForTabs', 'categoryProducts', 'categoryProductCounts',
            'featuredPosts'
        ));
    }

    /**
     * Hydrate mảng phẳng → Collection stdClass.
     * Cực kỳ quan trọng vì Data lấy từ cache là array thuần (để cache nhỏ & deserialize nhanh),
     * nhưng Blade View được code để expect Object ($p->name thay vì $p['name']).
     */
    private function hydrateProducts(array $items): \Illuminate\Support\Collection
    {
        return collect($items)->map(function ($i) {
            $i = (object)$i;
            $i->primaryImage    = (object)($i->primaryImage ?? []);
            $i->primary_image   = (object)($i->primary_image ?? []);
            $i->primaryCategory = (object)($i->primaryCategory ?? []);
            return $i;
        });
    }

    /**
     * Fetch sản phẩm bằng raw SQL — 3 queries sạch:
     *   1. JOIN products + categories + image đầu tiên
     *   2. GROUP BY comments → count + avg (KHÔNG có correlated subquery)
     *   3. variants WHERE IN cho toàn batch
     *
     * frame/label tính từ PHP (mirror accessor Product::getFrameAttribute / getLabelAttribute)
     * Chỉ dùng với WHERE clause KHÔNG chứa JSON_CONTAINS.
     */
    private function fetchProductsRaw(string $where, array $bindings, int $limit): array
    {
        // Query 1: products + category + image
        $sql = "
            SELECT
                p.id, p.sku, p.name, p.slug,
                p.price, p.sale_price, p.stock_quantity, p.is_featured,
                c.name AS cat_name,
                c.slug AS cat_slug,
                i.url  AS img_url,
                i.alt  AS img_alt
            FROM products p
            LEFT JOIN categories c ON c.id = p.primary_category_id
            LEFT JOIN images i
                ON i.id = CAST(JSON_UNQUOTE(JSON_EXTRACT(p.image_ids, '$[0]')) AS UNSIGNED)
            WHERE {$where}
            LIMIT {$limit}
        ";

        $rows = DB::select($sql, $bindings);
        if (empty($rows)) return [];

        $ids = array_column($rows, 'id');
        $ph  = implode(',', array_fill(0, count($ids), '?'));

        // Query 2: comment stats — 1 GROUP BY thay vì N×2 correlated subqueries
        $commentStats = DB::select(
            "SELECT commentable_id,
                    COUNT(*) AS cnt,
                    AVG(CASE WHEN rating IS NOT NULL THEN rating END) AS avg_rating
             FROM comments
             WHERE commentable_id IN ({$ph})
               AND commentable_type = 'App\\\\Models\\\\Product'
               AND is_approved = 1
               AND parent_id IS NULL
             GROUP BY commentable_id",
            $ids
        );
        $commentMap = [];
        foreach ($commentStats as $cs) {
            $commentMap[$cs->commentable_id] = $cs;
        }

        // Query 3: variants 1 WHERE IN cho toàn batch
        $variantsRaw = DB::select(
            "SELECT id, product_id, name, price, sale_price, stock_quantity, is_active, attributes
             FROM product_variants
             WHERE product_id IN ({$ph}) AND is_active = 1
             ORDER BY sort_order ASC",
            $ids
        );
        $varMap = [];
        foreach ($variantsRaw as $v) {
            $varMap[$v->product_id][] = $v;
        }

        return array_map(function ($r) use ($varMap, $commentMap) {
            // Normalize image URL
            $imgUrl = !empty($r->img_url)
                ? basename(preg_replace('#^clients/assets/img/clothes/#', '', ltrim($r->img_url, '/')))
                : 'no-image.webp';
            if (empty($imgUrl)) $imgUrl = 'no-image.webp';

            $onSaleProduct = $r->sale_price > 0 && $r->sale_price < $r->price;
            $cartPrice     = $onSaleProduct ? (float)$r->sale_price : (float)$r->price;

            // frame/label — mirror Product::getFrameAttribute() + getLabelAttribute()
            if ($r->is_featured) {
                $frame = 'frame-free-ship-hot.png';
                $label = 'Nổi bật';
            } elseif ($onSaleProduct) {
                $frame = 'frame-price-sale.png';
                $label = 'Giảm giá';
            } else {
                $frame = 'frame-free-ship-hot.png';
                $label = 'Bán chạy ' . date('Y');
            }

            // Comment stats
            $cs           = $commentMap[$r->id] ?? null;
            $commentCount = $cs ? (int)$cs->cnt : 0;
            $commentAvg   = $cs && $cs->avg_rating !== null ? (float)$cs->avg_rating : 5.0;

            // Variants
            $variantsData = [];
            foreach ($varMap[$r->id] ?? [] as $v) {
                $attrs = is_string($v->attributes)
                    ? (json_decode($v->attributes, true) ?? [])
                    : (array)($v->attributes ?? []);
                $details = array_values(array_filter([
                    $attrs['size'] ?? null,
                    !empty($attrs['has_pot']) ? 'Có phụ kiện đi kèm' : null,
                    $attrs['combo_type'] ?? null,
                    $attrs['notes'] ?? null,
                ]));
                $sp      = (float)($v->sale_price ?? 0);
                $pr      = (float)$v->price;
                $vOnSale = $sp > 0 && $sp < $pr;
                $variantsData[] = [
                    'id'               => $v->id,
                    'name'             => $v->name,
                    'price'            => $pr,
                    'sale_price'       => $sp ?: null,
                    'display_price'    => $vOnSale ? $sp : $pr,
                    'stock_quantity'   => $v->stock_quantity,
                    'is_active'        => (bool)$v->is_active,
                    'details'          => $details,
                    'is_on_sale'       => $vOnSale,
                    'discount_percent' => ($vOnSale && $pr > 0) ? round((1 - $sp / $pr) * 100) : 0,
                ];
            }

            return [
                'id'                      => $r->id,
                'sku'                     => $r->sku,
                'name'                    => $r->name,
                'slug'                    => $r->slug,
                'price'                   => (float)$r->price,
                'sale_price'              => $r->sale_price ? (float)$r->sale_price : null,
                'cart_price'              => $cartPrice,
                'stock_quantity'          => (int)$r->stock_quantity,
                'is_featured'             => (bool)$r->is_featured,
                'approved_comments_count' => $commentCount,
                'approved_rating_avg'     => $commentAvg,
                'primaryImage'            => ['url' => $imgUrl, 'alt' => $r->img_alt ?? $r->name],
                'primary_image'           => ['url' => $imgUrl, 'alt' => $r->img_alt ?? $r->name],
                'frame'                   => $frame,
                'label'                   => $label,
                'primaryCategory'         => ['name' => $r->cat_name ?? 'Danh mục', 'slug' => $r->cat_slug ?? ''],
                'display_rating_star'     => $commentCount > 0 ? (int)round($commentAvg) : 5,
                'display_review_count'    => $commentCount > 0 ? $commentCount : rand(10, 1000),
                'variants_data'           => $variantsData,
            ];
        }, $rows);
    }
}
