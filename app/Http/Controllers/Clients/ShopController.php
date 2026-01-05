<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class ShopController extends Controller
{
    public function index(Request $request, ?string $slug = null)
    {
        // Reject old format tags[]=id or tags=id - redirect to 404
        if ($request->has('tags')) {
            $tagsInput = $request->input('tags');

            // If tags is array (tags[]=id format)
            if (is_array($tagsInput)) {
                foreach ($tagsInput as $tag) {
                    if (is_numeric($tag)) {
                        return view('clients.pages.errors.404');
                    }
                }
            } else {
                // If tags is string (tags=id or tags=id1,id2 format)
                $tagsArray = explode(',', (string) $tagsInput);
                foreach ($tagsArray as $tag) {
                    $tag = trim($tag);
                    if (! empty($tag) && is_numeric($tag)) {
                        return view('clients.pages.errors.404');
                    }
                }
            }
        }

        $keyword = $request->input('keyword', '');
        $isImageSearch = $request->has('image_search');

        if ($keyword) {
            if (preg_match('/<\s*script|<\/\s*script\s*>|<[^>]+>/i', $keyword)) {
                return redirect()->route('client.shop.index')->with('error', 'Từ khóa không hợp lệ! Vui lòng thử lại!');
            }
        }
        $settings = View::shared('settings') ?? Setting::first();
        $keyword = $this->sanitizeKeyword($keyword);

        // Nếu là image search, thêm thông báo
        if ($isImageSearch && $keyword) {
            session()->flash('image_search_success', 'Đã tìm kiếm sản phẩm dựa trên hình ảnh với từ khóa: '.$keyword);
        }
        $filters = $this->resolveFilters($request);
        $categoryContext = $this->resolveCategoryContext($slug ?? $request->input('category'));

        if ($categoryContext['slug'] && ! $categoryContext['category']) {
            return view('clients.pages.errors.404');
        }

        $baseQuery = $this->baseProductQuery();

        if (! empty($categoryContext['ids'])) {
            $baseQuery->inCategory($categoryContext['ids']);
        }

        if ($keyword !== '') {
            $this->applyKeywordFilter($baseQuery, $keyword);
            $this->applyRelevanceOrdering($baseQuery, $keyword);
        }

        $filteredQuery = $this->applyFilters(clone $baseQuery, $filters);

        $productsForView = clone $filteredQuery;
        $productsMain = $this->buildProductListing(clone $filteredQuery, $filters, $keyword);
        $newProducts = $this->resolveNewProducts(clone $filteredQuery);

        $seoMeta = $this->prepareSeoMeta($settings, $categoryContext['category'], $keyword, $filters['tags'], $request);

        return view('clients.pages.shop.index', [
            'products' => $productsForView,
            'productsMain' => $productsMain,
            'newProducts' => $newProducts,
            'keyword' => $keyword,
            'selectedCategory' => $categoryContext['category'],
            'selectedCategorySlug' => $categoryContext['slug'],
            'category' => $categoryContext['category'],
            'perPage' => $filters['perPage'],
            'minPriceRange' => $filters['minPriceRange'],
            'maxPriceRange' => $filters['maxPriceRange'],
            'minRating' => $filters['minRating'],
            'tags' => $filters['tags'],
            'sort' => $filters['sort'],
            'pageTitle' => $seoMeta['title'],
            'pageDescription' => $seoMeta['description'],
            'pageKeywords' => $seoMeta['keywords'],
            'canonicalUrl' => $seoMeta['canonical'],
            'pageImage' => $seoMeta['image'],
        ]);
    }

    public function search(Request $request)
    {
        $keyword = $this->sanitizeKeyword($request->input('keyword', ''));

        if ($keyword === '') {
            return response()->json([]);
        }

        $products = Product::query()
            ->select(['id', 'name', 'slug', 'price', 'sale_price'])
            ->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('sku', 'like', "%{$keyword}%");
            })
            ->limit(10)
            ->get();

        return response()->json($products);
    }

    public function searchKeyword(Request $request)
    {
        $settings = View::shared('settings') ?? Setting::first();
        $keyword = $this->sanitizeKeyword($request->input('keyword', ''));
        $filters = $this->resolveFilters($request);
        $categoryContext = $this->resolveCategoryContext($request->input('category'));

        if ($keyword !== '' && ($skuProduct = $this->findProductBySku($keyword))) {
            return redirect()
                ->route('client.product.detail', $skuProduct->slug)
                ->with('success', 'Chuyển đến trang sản phẩm theo SKU: '.$skuProduct->sku);
        }

        $baseQuery = $this->baseProductQuery();

        if (! empty($categoryContext['ids'])) {
            $baseQuery->inCategory($categoryContext['ids']);
        }

        if ($keyword !== '') {
            $this->applyKeywordFilter($baseQuery, $keyword);
            $this->applyRelevanceOrdering($baseQuery, $keyword);
        }

        $filteredQuery = $this->applyFilters(clone $baseQuery, $filters);

        $productsForView = clone $filteredQuery;
        $productsMain = $this->buildProductListing(clone $filteredQuery, $filters, $keyword);
        $newProducts = $this->resolveNewProducts(clone $filteredQuery);

        $defaultSiteName = $settings->site_name ?? 'AutoSensor Việt Nam';
        $seoMeta = [
            'title' => "Kết quả tìm kiếm cho '{$keyword}' - {$defaultSiteName}",
            'description' => "Tìm thấy các sản phẩm liên quan đến '{$keyword}' tại {$defaultSiteName}.",
            'keywords' => $keyword.', shop '.$defaultSiteName.', thiết bị tự động hóa',
            'canonical' => ($settings->site_url ?? url('/')).'/shop/search?keyword='.urlencode($keyword),
            'image' => asset('clients/assets/img/business/'.($settings->site_banner ?? $settings->site_logo)),
        ];

        return view('clients.pages.shop.index', [
            'products' => $productsForView,
            'productsMain' => $productsMain,
            'newProducts' => $newProducts,
            'keyword' => $keyword,
            'selectedCategory' => $categoryContext['category'],
            'selectedCategorySlug' => $categoryContext['slug'],
            'category' => $categoryContext['category'],
            'perPage' => $filters['perPage'],
            'minPriceRange' => $filters['minPriceRange'],
            'maxPriceRange' => $filters['maxPriceRange'],
            'minRating' => $filters['minRating'],
            'tags' => $filters['tags'],
            'sort' => $filters['sort'],
            'pageTitle' => $seoMeta['title'],
            'pageDescription' => $seoMeta['description'],
            'pageKeywords' => $seoMeta['keywords'],
            'canonicalUrl' => $seoMeta['canonical'],
            'pageImage' => $seoMeta['image'],
        ]);
    }

    protected function baseProductQuery(): Builder
    {
        return Product::query()
            ->select([
                'id',
                'name',
                'slug',
                'sku',
                'price',
                'sale_price',
                'stock_quantity',
                'primary_category_id',
                'image_ids',
                'is_featured',
            ])
            ->active()
            ->withApprovedCommentsMeta()
            ->with('variants');
    }

    protected function resolveFilters(Request $request): array
    {
        $perPageOptions = [12, 24, 30, 48, 60];
        $perPage = (int) $request->input('perPage', 30);
        if (! in_array($perPage, $perPageOptions, true)) {
            $perPage = 30;
        }

        $minPriceRange = $request->filled('minPriceRange') ? max(0, (int) $request->input('minPriceRange')) : null;
        $maxPriceRange = $request->filled('maxPriceRange') ? max(0, (int) $request->input('maxPriceRange')) : null;

        if (! is_null($minPriceRange) && ! is_null($maxPriceRange) && $minPriceRange > $maxPriceRange) {
            [$minPriceRange, $maxPriceRange] = [$maxPriceRange, $minPriceRange];
        }

        $minRating = $request->filled('minRating') ? max(1, min(5, (int) $request->input('minRating'))) : null;

        $tags = $request->input('tags', []);
        if (! is_array($tags)) {
            $tags = explode(',', (string) $tags);
        }

        // Only accept slugs, convert to IDs for query
        $tagIds = [];
        foreach ($tags as $tag) {
            $tag = trim($tag);
            if (empty($tag)) {
                continue;
            }

            // Only accept slug format (non-numeric), ignore numeric IDs
            if (! is_numeric($tag)) {
                $tagModel = \App\Models\Tag::where('slug', $tag)->where('is_active', true)->first();
                if ($tagModel) {
                    $tagIds[] = $tagModel->id;
                }
            }
        }
        $tags = array_values(array_unique(array_filter($tagIds, fn ($id) => $id > 0)));

        $allowedSort = ['default', 'newest', 'price-asc', 'price-desc', 'name-asc', 'name-desc'];
        $sort = $request->input('sort', 'default');
        if (! in_array($sort, $allowedSort, true)) {
            $sort = 'default';
        }

        return [
            'perPage' => $perPage,
            'minPriceRange' => $minPriceRange,
            'maxPriceRange' => $maxPriceRange,
            'minRating' => $minRating,
            'tags' => $tags,
            'sort' => $sort,
        ];
    }

    protected function resolveCategoryContext(?string $slug): array
    {
        $slug = $this->sanitizeSlug($slug);
        if (! $slug) {
            return ['category' => null, 'ids' => [], 'slug' => null];
        }

        $category = Category::query()
            ->with('parent')
            ->active()
            ->where('slug', $slug)
            ->first();

        if (! $category) {
            return ['category' => null, 'ids' => [], 'slug' => $slug];
        }

        $ids = $this->collectDescendantIds($category);

        return ['category' => $category, 'ids' => $ids, 'slug' => $slug];
    }

    protected function collectDescendantIds(Category $category): array
    {
        $ids = [$category->id];
        $currentLevel = [$category->id];

        while (! empty($currentLevel)) {
            $children = Category::query()
                ->whereIn('parent_id', $currentLevel)
                ->pluck('id')
                ->all();

            if (empty($children)) {
                break;
            }

            $ids = array_merge($ids, $children);
            $currentLevel = $children;
        }

        return array_values(array_unique($ids));
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        $priceExpression = $this->priceExpression();

        if (! is_null($filters['minPriceRange']) && ! is_null($filters['maxPriceRange'])) {
            $query->whereBetween(DB::raw($priceExpression), [$filters['minPriceRange'], $filters['maxPriceRange']]);
        } elseif (! is_null($filters['minPriceRange'])) {
            $query->where(DB::raw($priceExpression), '>=', $filters['minPriceRange']);
        } elseif (! is_null($filters['maxPriceRange'])) {
            $query->where(DB::raw($priceExpression), '<=', $filters['maxPriceRange']);
        }

        if (! is_null($filters['minRating'])) {
            $query->whereHas('comments', function ($q) use ($filters) {
                $q->where('is_approved', true)->where('rating', '>=', $filters['minRating']);
            });
        }

        if (! empty($filters['tags'])) {
            $query->where(function ($q) use ($filters) {
                foreach ($filters['tags'] as $tagId) {
                    $q->orWhereJsonContains('tag_ids', $tagId);
                }
            });
        }

        return $query;
    }

    protected function applyKeywordFilter(Builder $query, string $keyword): void
    {
        $words = array_filter(explode(' ', $keyword));

        $query->where(function ($q) use ($keyword, $words) {
            $q->where('name', 'like', "%{$keyword}%")
                ->orWhere('slug', 'like', "%{$keyword}%")
                ->orWhere('sku', 'like', "%{$keyword}%");

            foreach ($words as $word) {
                $q->orWhere('name', 'like', "%{$word}%")
                    ->orWhere('slug', 'like', "%{$word}%")
                    ->orWhere('sku', 'like', "%{$word}%");
            }
        });
    }

    protected function buildProductListing(Builder $query, array $filters, ?string $keyword = null)
    {
        // Khi có từ khóa và sort = default thì ưu tiên sort theo độ liên quan,
        // không override bằng sort mặc định theo ngày tạo.
        if ($keyword === null || $keyword === '' || $filters['sort'] !== 'default') {
            $this->applySorting($query, $filters['sort']);
        }

        $paginator = $query
            ->paginate($filters['perPage'])
            ->withQueryString();

        // Preload images để tránh N+1 queries
        Product::preloadImages($paginator->items());

        return $paginator;
    }

    /**
     * Sắp xếp theo độ liên quan khi có từ khóa:
     *  - Ưu tiên khớp chính xác (name/slug/sku = keyword) lên đầu
     *  - Sau đó khớp theo cụm từ (LIKE %keyword%)
     *  - Cuối cùng là các kết quả khớp theo từng từ (đã được applyKeywordFilter() đưa vào WHERE)
     */
    protected function applyRelevanceOrdering(Builder $query, string $keyword): void
    {
        $normalized = mb_strtolower($keyword);
        $likePhrase = '%'.$normalized.'%';

        $query->orderByRaw(
            'CASE
                WHEN LOWER(name) = ? OR LOWER(slug) = ? OR LOWER(sku) = ? THEN 0
                WHEN LOWER(name) LIKE ? OR LOWER(slug) LIKE ? OR LOWER(sku) LIKE ? THEN 1
                ELSE 2
            END',
            [
                $normalized,
                $normalized,
                $normalized,
                $likePhrase,
                $likePhrase,
                $likePhrase,
            ]
        )->orderBy('created_at', 'desc');
    }

    protected function applySorting(Builder $query, string $sort): void
    {
        $priceExpression = $this->priceExpression();

        switch ($sort) {
            case 'price-asc':
                $query->orderByRaw("{$priceExpression} ASC")->orderBy('created_at', 'desc');
                break;
            case 'price-desc':
                $query->orderByRaw("{$priceExpression} DESC")->orderBy('created_at', 'desc');
                break;
            case 'name-asc':
                $query->orderBy('name')->orderBy('created_at', 'desc');
                break;
            case 'name-desc':
                $query->orderBy('name', 'desc')->orderBy('created_at', 'desc');
                break;
            case 'newest':
            case 'default':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }
    }

    protected function resolveNewProducts(Builder $query)
    {
        $products = $query
            ->orderBy('created_at', 'desc')
            ->limit(4)
            ->get();

        // Preload images để tránh N+1 queries
        Product::preloadImages($products);

        return $products;
    }

    protected function prepareSeoMeta(object $settings, ?Category $category, string $keyword, array $tagIds = [], ?\Illuminate\Http\Request $request = null): array
    {
        $defaultSiteName = $settings->site_name ?? 'AutoSensor Việt Nam';

        // Xử lý tags nếu có
        $tagNames = [];
        if (! empty($tagIds)) {
            $tags = \App\Models\Tag::whereIn('id', $tagIds)
                ->where('is_active', true)
                ->pluck('name')
                ->toArray();
            $tagNames = array_filter($tags);
        }

        // Trường hợp có tags
        if (! empty($tagNames)) {
            $tagNameStr = implode(', ', $tagNames);
            $tagCount = count($tagNames);

            // Title format: Thẻ sản phẩm: "Tag1, Tag2" - SiteName
            $title = 'Thẻ sản phẩm: "'.$tagNameStr.'" - '.$defaultSiteName;

            // Description không cắt
            if ($tagCount === 1) {
                $description = 'Khám phá bộ sưu tập sản phẩm '.$tagNameStr.' chất lượng cao tại '.$defaultSiteName.'. Đa dạng mẫu mã, giá tốt, giao hàng nhanh.';
            } else {
                $description = 'Tổng hợp sản phẩm '.$tagNameStr.' đa dạng tại '.$defaultSiteName.'. Chất lượng tốt, giá ưu đãi, ship toàn quốc.';
            }

            // Canonical URL cho tags: dùng slug từ request
            $tagsSlug = $request ? $request->input('tags') : null;
            if ($tagsSlug) {
                // Nếu tags là array, chuyển thành string
                if (is_array($tagsSlug)) {
                    $tagsSlug = implode(',', $tagsSlug);
                }
                $canonicalUrl = route('client.shop.index', ['tags' => $tagsSlug]);
            } else {
                $canonicalUrl = url()->current();
            }

            return [
                'title' => $title,
                'description' => $description,
                'keywords' => $tagNameStr.', sản phẩm '.$tagNameStr.', '.$defaultSiteName.', thiết bị tự động hóa, giải pháp công nghiệp',
                'canonical' => $canonicalUrl,
                'image' => asset('clients/assets/img/business/'.($settings->site_banner ?? $settings->site_logo)),
            ];
        }

        if ($category) {
            // Lấy metadata từ JSON array
            $metadata = $category->metadata ?? [];
            $metaTitle = $metadata['meta_title'] ?? null;
            $metaDescription = $metadata['meta_description'] ?? null;
            $metaKeywords = $metadata['meta_keywords'] ?? null;
            $metaCanonical = $metadata['meta_canonical'] ?? null;

            // Title không cắt
            $title = $metaTitle
                ? $metaTitle.' – '.$defaultSiteName
                : $category->name.' - '.$defaultSiteName;

            // Description không cắt
            $description = $metaDescription
                ?? strip_tags($category->description ?: 'Khám phá các sản phẩm '.$category->name.' chất lượng tại '.$defaultSiteName.'. Đa dạng mẫu mã, giá tốt.');

            return [
                'title' => $title,
                'description' => $description,
                'keywords' => $metaKeywords
                    ?? $category->name.', thiết bị tự động hóa, giải pháp công nghiệp, '.$defaultSiteName,
                'canonical' => $metaCanonical
                    ?? ($settings->site_url ? $settings->site_url.'/'.$category->slug : url()->current()),
                'image' => $category->image
                    ? asset('clients/assets/img/categories/'.$category->image)
                    : asset('clients/assets/img/business/'.($settings->site_banner ?? $settings->site_logo)),
            ];
        }

        if ($keyword !== '') {
            // Title không cắt
            $title = 'Kết quả tìm kiếm "'.$keyword.'" - '.$defaultSiteName;

            // Description không cắt
            $description = 'Tìm thấy các sản phẩm liên quan đến "'.$keyword.'" tại '.$defaultSiteName.'. Đa dạng mẫu mã, chất lượng tốt, giá ưu đãi.';

            return [
                'title' => $title,
                'description' => $description,
                'keywords' => $keyword.', shop '.$defaultSiteName.', thiết bị tự động hóa, tìm kiếm',
                'canonical' => url()->current(),
                'image' => asset('clients/assets/img/business/'.($settings->site_banner ?? $settings->site_logo)),
            ];
        }

        // Trang shop chính: Title và Description tối ưu cho SEO
        $title = 'Cửa Hàng Thiết Bị Tự Động Hóa AutoSensor Việt Nam – Cảm Biến, PLC, HMI, Biến Tần';
        $description = 'Cửa hàng AutoSensor Việt Nam chuyên cung cấp thiết bị tự động hóa công nghiệp: cảm biến, PLC, HMI, biến tần, servo, encoder, rơ le. Phù hợp cho nhà máy, dây chuyền sản xuất và hệ thống điều khiển công nghiệp.';

        return [
            'title' => $title,
            'description' => $description,
            'keywords' => 'cửa hàng thiết bị tự động hóa, shop thiết bị công nghiệp, '.$defaultSiteName.', cảm biến, PLC, HMI, biến tần, giải pháp tự động hóa',
            'canonical' => $settings->site_url ? $settings->site_url.'/cua-hang' : url()->current(),
            'image' => asset('clients/assets/img/business/'.($settings->site_banner ?? $settings->site_logo)),
        ];
    }

    protected function findProductBySku(string $keyword): ?Product
    {
        return Product::active()
            ->whereRaw('LOWER(sku) = ?', [strtolower($keyword)])
            ->first();
    }

    protected function sanitizeKeyword(?string $keyword): string
    {
        if (! $keyword) {
            return '';
        }

        $keyword = strip_tags($keyword);
        $keyword = preg_replace('/[^\p{L}\p{N}\s\-]/u', '', $keyword);
        $keyword = preg_replace('/\s+/u', ' ', $keyword);
        $keyword = mb_substr($keyword, 0, 100);

        return trim($keyword);
    }

    protected function sanitizeSlug(?string $slug): ?string
    {
        if (! $slug) {
            return null;
        }

        $slug = Str::slug(strip_tags($slug));

        return $slug === '' ? null : $slug;
    }

    protected function priceExpression(): string
    {
        return 'COALESCE(NULLIF(sale_price, 0), price)';
    }
}
