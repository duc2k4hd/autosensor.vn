<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

        // Validate keyword - chống XSS và các ký tự không hợp lệ
        if ($keyword) {
            // Kiểm tra HTML/script tags
            if (preg_match('/<\s*script|<\/\s*script\s*>|<[^>]+>/i', $keyword)) {
                return redirect()->route('client.shop.index')->with('error', 'Từ khóa không hợp lệ! Vui lòng thử lại!');
            }
            
            // Kiểm tra độ dài quá lớn (có thể là DoS attempt)
            if (mb_strlen($keyword) > 500) {
                return redirect()->route('client.shop.index')->with('error', 'Từ khóa quá dài! Vui lòng thử lại!');
            }
        }
        $settings = View::shared('settings') ?? Setting::first();
        $keyword = $this->sanitizeKeyword($keyword);

        // Nếu là image search, thêm thông báo
        if ($isImageSearch && $keyword) {
            session()->flash('image_search_success', 'Đã tìm kiếm sản phẩm dựa trên hình ảnh với từ khóa: '.$keyword);
        }
        $filters = $this->resolveFilters($request);
        
        // Sanitize category input từ request
        $categoryInput = $slug ?? $request->input('category');
        if ($categoryInput && ! is_string($categoryInput)) {
            $categoryInput = null; // Chỉ chấp nhận string
        }
        $categoryContext = $this->resolveCategoryContext($categoryInput);

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

        // Lấy danh sách brands để hiển thị filter
        $allBrands = \App\Models\Brand::active()
            ->ordered()
            ->select('id', 'name', 'slug', 'image')
            ->get();

        // Lấy brands đã chọn từ filters
        $selectedBrandSlugs = [];
        if (! empty($filters['brands']) && is_array($filters['brands'])) {
            $selectedBrands = \App\Models\Brand::whereIn('id', $filters['brands'])
                ->select('slug')
                ->pluck('slug')
                ->toArray();
            $selectedBrandSlugs = $selectedBrands;
        }

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
            'brands' => $filters['brands'],
            'brand' => $filters['brand'] ?? null, // Backward compatibility
            'selectedBrandSlugs' => $selectedBrandSlugs, // Brands đã chọn (slugs)
            'allBrands' => $allBrands, // Tất cả brands để hiển thị
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

        // Lấy danh sách brands để hiển thị filter
        $allBrands = \App\Models\Brand::active()
            ->ordered()
            ->select('id', 'name', 'slug', 'image')
            ->get();

        // Lấy brands đã chọn từ filters
        $selectedBrandSlugs = [];
        if (! empty($filters['brands']) && is_array($filters['brands'])) {
            $selectedBrands = \App\Models\Brand::whereIn('id', $filters['brands'])
                ->select('slug')
                ->pluck('slug')
                ->toArray();
            $selectedBrandSlugs = $selectedBrands;
        }

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
            'brands' => $filters['brands'],
            'brand' => $filters['brand'] ?? null, // Backward compatibility
            'selectedBrandSlugs' => $selectedBrandSlugs, // Brands đã chọn (slugs)
            'allBrands' => $allBrands, // Tất cả brands để hiển thị
            'sort' => $filters['sort'],
            'pageTitle' => $seoMeta['title'],
            'pageDescription' => $seoMeta['description'],
            'pageKeywords' => $seoMeta['keywords'],
            'canonicalUrl' => $seoMeta['canonical'],
            'pageImage' => $seoMeta['image'],
        ]);
    }

    /**
     * Xử lý URL category-brand combo: /{category-slug}-{brand-slug}
     * Ví dụ: /cam-bien-omron, /cam-bien-tiem-can-panasonic
     * 
     * Logic:
     * 1. Parse URL để tách category-slug và brand-slug
     * 2. Validate cả 2 đều tồn tại và active trong DB
     * 3. Nếu không tồn tại → 404 cứng (không redirect)
     * 4. Filter products: category + brand
     * 5. SEO meta riêng cho combo này
     */
    public function categoryBrand(string $categorySlug, string $brandSlug, Request $request)
    {
        // QUAN TRỌNG: Kiểm tra product slug TRƯỚC để tránh conflict
        // Nếu slug này là product slug, forward về ProductController ngay
        $fullSlug = $categorySlug.'-'.$brandSlug;
        
        // Kiểm tra product slug TRƯỚC (không cache để đảm bảo chính xác)
        // Vì route này match trước nên cần check kỹ
        $productExists = Product::where('slug', $fullSlug)
            ->active()
            ->select('id')
            ->exists();
        
        // Nếu là product slug, forward về ProductController ngay
        if ($productExists) {
            // Invalidate cache nếu có để đảm bảo consistency
            Cache::forget('slug_type_'.$fullSlug);
            $productController = app(\App\Http\Controllers\Clients\ProductController::class);
            return $productController->detail($fullSlug);
        }
        
        // Validate và load category + brand từ DB
        // Cache để tránh query lặp lại
        $cacheKey = "category_brand_check_{$categorySlug}_{$brandSlug}";
        $categoryBrandData = Cache::remember($cacheKey, 3600, function () use ($categorySlug, $brandSlug) {
            $category = Category::where('slug', $categorySlug)
                ->active()
                ->first();
            
            if (!$category) {
                return null;
            }
            
            $brand = Brand::where('slug', $brandSlug)
                ->active()
                ->first();
            
            if (!$brand) {
                return null;
            }
            
            return [
                'category' => $category,
                'brand' => $brand,
            ];
        });
        
        // Nếu không tìm thấy category hoặc brand → kiểm tra xem có phải là category slug đơn không
        // Nếu là category slug đơn (ví dụ: /cam-bien bị tách nhầm thành cam + bien), forward về route /{slug}
        if (!$categoryBrandData) {
            // Kiểm tra xem fullSlug có phải là category slug hợp lệ không
            $categoryCheck = Category::where('slug', $fullSlug)
                ->active()
                ->exists();
            
            // Nếu fullSlug là category hợp lệ, forward về ProductController để xử lý
            if ($categoryCheck) {
                $productController = app(\App\Http\Controllers\Clients\ProductController::class);
                return $productController->detail($fullSlug);
            }
            
            // Nếu không phải category và không phải product, return 404
            return view('clients.pages.errors.404');
        }
        
        $category = $categoryBrandData['category'];
        $brand = $categoryBrandData['brand'];
        
        // Lấy settings
        $settings = View::shared('settings') ?? Setting::first();
        
        // Sanitize keyword nếu có
        $keyword = $this->sanitizeKeyword($request->input('keyword', ''));
        
        // Resolve filters (không có brand trong filter vì đã filter bằng URL)
        $filters = $this->resolveFilters($request);
        // Override brand filter với brand từ URL
        $filters['brands'] = [$brand->id];
        
        // Resolve category context
        $categoryContext = $this->resolveCategoryContext($categorySlug);
        
        if (!$categoryContext['category'] || $categoryContext['category']->id !== $category->id) {
            return view('clients.pages.errors.404');
        }
        
        // Base query với category filter
        $baseQuery = $this->baseProductQuery();
        
        if (!empty($categoryContext['ids'])) {
            $baseQuery->inCategory($categoryContext['ids']);
        }
        
        // Apply brand filter (từ URL, không từ query string)
        $baseQuery->where('brand_id', $brand->id);
        
        // Apply keyword filter nếu có
        if ($keyword !== '') {
            $this->applyKeywordFilter($baseQuery, $keyword);
            $this->applyRelevanceOrdering($baseQuery, $keyword);
        }
        
        // Apply các filters khác (price, rating, tags)
        $filteredQuery = $this->applyFilters(clone $baseQuery, $filters);
        
        // Build product listing
        $productsForView = clone $filteredQuery;
        $productsMain = $this->buildProductListing(clone $filteredQuery, $filters, $keyword);
        $newProducts = $this->resolveNewProducts(clone $filteredQuery);
        
        // Prepare SEO meta cho category-brand combo
        $seoMeta = $this->prepareCategoryBrandSeoMeta($settings, $category, $brand, $keyword);
        
        // Lấy danh sách brands để hiển thị filter (tất cả brands, không chỉ brand hiện tại)
        $allBrands = Brand::active()
            ->ordered()
            ->select('id', 'name', 'slug', 'image')
            ->get();
        
        // Selected brand slugs (chỉ brand hiện tại)
        $selectedBrandSlugs = [$brand->slug];
        
        return view('clients.pages.shop.index', [
            'products' => $productsForView,
            'productsMain' => $productsMain,
            'newProducts' => $newProducts,
            'keyword' => $keyword,
            'selectedCategory' => $category,
            'selectedCategorySlug' => $categorySlug,
            'category' => $category,
            'selectedBrand' => $brand, // Thêm brand vào view
            'selectedBrandSlug' => $brandSlug, // Thêm brand slug vào view
            'perPage' => $filters['perPage'],
            'minPriceRange' => $filters['minPriceRange'],
            'maxPriceRange' => $filters['maxPriceRange'],
            'minRating' => $filters['minRating'],
            'tags' => $filters['tags'],
            'brands' => $filters['brands'],
            'brand' => $brand->id, // Backward compatibility
            'selectedBrandSlugs' => $selectedBrandSlugs,
            'allBrands' => $allBrands,
            'sort' => $filters['sort'],
            'pageTitle' => $seoMeta['title'],
            'pageDescription' => $seoMeta['description'],
            'pageKeywords' => $seoMeta['keywords'],
            'canonicalUrl' => $seoMeta['canonical'],
            'pageImage' => $seoMeta['image'],
            'isCategoryBrandPage' => true, // Flag để view biết đây là category-brand page
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
                'brand_id',
                'image_ids',
                'is_featured',
            ])
            ->active()
            ->withApprovedCommentsMeta()
            ->with(['variants', 'brand']);
    }

    protected function resolveFilters(Request $request): array
    {
        $perPageOptions = [24, 30, 36, 48, 60, 72, 96];
        $perPage = (int) $request->input('perPage', 30);
        if (! in_array($perPage, $perPageOptions, true)) {
            $perPage = 30;
        }

        // Price range validation - chống SQL injection và giá trị không hợp lệ
        $minPriceRange = null;
        $maxPriceRange = null;
        
        if ($request->filled('minPriceRange')) {
            $minPrice = $request->input('minPriceRange');
            // Chỉ chấp nhận số nguyên dương, giới hạn tối đa 999,999,999
            if (is_numeric($minPrice) && $minPrice >= 0 && $minPrice <= 999999999) {
                $minPriceRange = (int) $minPrice;
            }
        }
        
        if ($request->filled('maxPriceRange')) {
            $maxPrice = $request->input('maxPriceRange');
            // Chỉ chấp nhận số nguyên dương, giới hạn tối đa 999,999,999
            if (is_numeric($maxPrice) && $maxPrice >= 0 && $maxPrice <= 999999999) {
                $maxPriceRange = (int) $maxPrice;
            }
        }

        // Tự động swap nếu min > max
        if (! is_null($minPriceRange) && ! is_null($maxPriceRange) && $minPriceRange > $maxPriceRange) {
            [$minPriceRange, $maxPriceRange] = [$maxPriceRange, $minPriceRange];
        }

        // Rating validation - chỉ chấp nhận 1-5
        $minRating = null;
        if ($request->filled('minRating')) {
            $rating = $request->input('minRating');
            if (is_numeric($rating) && $rating >= 1 && $rating <= 5) {
                $minRating = (int) $rating;
            }
        }

        // Tags filter - sanitize and validate
        $tags = $request->input('tags', []);
        if (! is_array($tags)) {
            $tags = explode(',', (string) $tags);
        }

        // Only accept slugs, convert to IDs for query
        $tagIds = [];
        $maxTags = 10; // Giới hạn số lượng tags để tránh query quá phức tạp
        
        foreach ($tags as $tag) {
            if (count($tagIds) >= $maxTags) {
                break; // Giới hạn số lượng tags
            }
            
            $tag = $this->sanitizeSlug(trim((string) $tag));
            if (empty($tag)) {
                continue;
            }

            // Validate slug format: chỉ cho phép a-z, 0-9, - (không có ký tự đặc biệt, SQL injection, XSS)
            if (preg_match('/^[a-z0-9\-]+$/', $tag) && strlen($tag) <= 100) {
                $tagModel = \App\Models\Tag::where('slug', $tag)->where('is_active', true)->first();
                if ($tagModel) {
                    $tagIds[] = $tagModel->id;
                }
            }
        }
        $tags = array_values(array_unique(array_filter($tagIds, fn ($id) => $id > 0)));

        // Brands filter - accept multiple brand slugs (comma-separated) or single brand
        // Support both 'brands' (new format) and 'brand' (backward compatibility)
        $brandsInput = $request->input('brands', $request->input('brand'));
        $brandSlugs = [];
        $brandIds = [];
        $maxBrands = 20; // Giới hạn số lượng brands để tránh query quá phức tạp
        
        if ($brandsInput) {
            // Handle both string (comma-separated) and array
            if (is_array($brandsInput)) {
                $brandSlugs = $brandsInput;
            } else {
                // Giới hạn độ dài input để tránh DoS
                $brandsInput = mb_substr((string) $brandsInput, 0, 500);
                $brandSlugs = explode(',', $brandsInput);
            }
            
            // Sanitize và validate từng brand slug
            foreach ($brandSlugs as $brandSlug) {
                if (count($brandIds) >= $maxBrands) {
                    break; // Giới hạn số lượng brands
                }
                
                $brandSlug = $this->sanitizeSlug(trim((string) $brandSlug));
                if (empty($brandSlug)) {
                    continue;
                }
                
                // Validate slug format: chỉ cho phép a-z, 0-9, - (không có ký tự đặc biệt, SQL injection, XSS)
                if (preg_match('/^[a-z0-9\-]+$/', $brandSlug) && strlen($brandSlug) <= 100) {
                    $brandModel = \App\Models\Brand::where('slug', $brandSlug)->where('is_active', true)->first();
                    if ($brandModel) {
                        $brandIds[] = $brandModel->id;
                    }
                }
            }
        }
        $brands = array_values(array_unique(array_filter($brandIds, fn ($id) => $id > 0)));

        // Sort validation - chỉ chấp nhận các giá trị được phép
        $allowedSort = ['default', 'newest', 'price-asc', 'price-desc', 'name-asc', 'name-desc'];
        $sort = $request->input('sort', 'default');
        // Sanitize và validate sort value
        $sort = is_string($sort) ? trim($sort) : 'default';
        if (! in_array($sort, $allowedSort, true)) {
            $sort = 'default';
        }

        // Expert filter (preset filters for engineers)
        $expertFilter = null;
        $allowedExpertFilters = ['high_temp', 'chemical', 'ip67', 'high_accuracy'];
        if ($request->filled('expert_filter')) {
            $expertInput = $request->input('expert_filter');
            if (is_string($expertInput) && in_array($expertInput, $allowedExpertFilters, true)) {
                $expertFilter = $expertInput;
            }
        }

        return [
            'perPage' => $perPage,
            'minPriceRange' => $minPriceRange,
            'maxPriceRange' => $maxPriceRange,
            'minRating' => $minRating,
            'tags' => $tags,
            'brands' => $brands, // Changed from 'brand' to 'brands' (array)
            'brand' => ! empty($brands) ? $brands[0] : null, // Keep for backward compatibility
            'sort' => $sort,
            'expert_filter' => $expertFilter,
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
        $maxDepth = 10; // Giới hạn độ sâu để tránh infinite loop hoặc query quá phức tạp
        $depth = 0;

        while (! empty($currentLevel) && $depth < $maxDepth) {
            // Đảm bảo tất cả IDs đều là integer dương
            $validIds = array_filter($currentLevel, function ($id) {
                return is_numeric($id) && $id > 0 && $id <= PHP_INT_MAX;
            });
            
            if (empty($validIds)) {
                break;
            }
            
            $children = Category::query()
                ->whereIn('parent_id', $validIds)
                ->active() // Chỉ lấy category active
                ->pluck('id')
                ->all();

            if (empty($children)) {
                break;
            }

            $ids = array_merge($ids, $children);
            $currentLevel = $children;
            $depth++;
        }

        // Đảm bảo tất cả IDs đều là integer dương và unique
        $validIds = array_filter($ids, function ($id) {
            return is_numeric($id) && $id > 0 && $id <= PHP_INT_MAX;
        });

        return array_values(array_unique($validIds));
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

        // Filter by tags - sử dụng whereIn an toàn với array đã validate
        if (! empty($filters['tags']) && is_array($filters['tags'])) {
            // Đảm bảo tất cả tag IDs đều là integer dương
            $validTagIds = array_filter($filters['tags'], function ($id) {
                return is_numeric($id) && $id > 0 && $id <= PHP_INT_MAX;
            });
            
            if (! empty($validTagIds)) {
                $query->where(function ($q) use ($validTagIds) {
                    foreach ($validTagIds as $tagId) {
                        $q->orWhereJsonContains('tag_ids', (int) $tagId);
                    }
                });
            }
        }

        // Filter by brands (multiple brands support) - sử dụng whereIn an toàn
        if (! empty($filters['brands']) && is_array($filters['brands'])) {
            // Đảm bảo tất cả brand IDs đều là integer dương
            $validBrandIds = array_filter($filters['brands'], function ($id) {
                return is_numeric($id) && $id > 0 && $id <= PHP_INT_MAX;
            });
            
            if (! empty($validBrandIds)) {
                $query->whereIn('brand_id', $validBrandIds);
            }
        } elseif (! empty($filters['brand']) && is_numeric($filters['brand']) && $filters['brand'] > 0) {
            // Backward compatibility: single brand filter
            $query->where('brand_id', (int) $filters['brand']);
        }

        // Expert filter (preset filters for engineers)
        if (! empty($filters['expert_filter'])) {
            $this->applyExpertFilter($query, $filters['expert_filter']);
        }

        return $query;
    }

    /**
     * Áp dụng bộ lọc chuyên gia dựa trên keywords kỹ thuật
     */
    protected function applyExpertFilter(Builder $query, string $expertFilter): void
    {
        $expertKeywords = [
            'high_temp' => ['nhiệt độ cao', 'chịu nhiệt', 'high temp', 'temperature', 'nhiệt độ', 'chịu nhiệt độ', 'nhiệt độ làm việc'],
            'chemical' => ['hóa chất', 'chemical', 'chống ăn mòn', 'corrosion', 'acid', 'kiềm', 'hóa học', 'môi trường hóa chất'],
            'ip67' => ['IP67', 'IP68', 'IP69', 'chống bụi', 'chống nước', 'waterproof', 'dustproof', 'IP65', 'IP66', 'bảo vệ IP'],
            'high_accuracy' => ['độ chính xác', 'accuracy', 'precision', 'chính xác cao', 'độ chính xác cao', 'precision', 'tolerance'],
        ];

        if (! isset($expertKeywords[$expertFilter])) {
            return;
        }

        $keywords = $expertKeywords[$expertFilter];

        $query->where(function ($q) use ($keywords) {
            foreach ($keywords as $keyword) {
                $q->orWhere('name', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%")
                    ->orWhere('short_description', 'like', "%{$keyword}%")
                    ->orWhere('sku', 'like', "%{$keyword}%");
            }
        });
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

    /**
     * Prepare SEO meta cho category-brand combo
     * Tạo meta riêng biệt, không duplicate với category thuần
     */
    protected function prepareCategoryBrandSeoMeta(object $settings, Category $category, Brand $brand, string $keyword = ''): array
    {
        $defaultSiteName = $settings->site_name ?? 'AutoSensor Việt Nam';
        $siteUrl = $settings->site_url ?? config('app.url');
        $siteUrl = rtrim($siteUrl, '/');
        
        // URL canonical cho category-brand combo
        $canonicalUrl = "{$siteUrl}/{$category->slug}-{$brand->slug}";
        
        // Title: "{Category} {Brand} - Chính hãng, Giá tốt 2026 | SiteName"
        // Ví dụ: "Cảm biến Omron - Chính hãng, Giá tốt 2026 | AutoSensor Việt Nam"
        $title = "{$category->name} {$brand->name} - Chính hãng, Giá tốt 2026 | {$defaultSiteName}";
        
        // Description: Mô tả riêng cho combo category-brand
        // Không copy từ category description
        $categoryName = $category->name;
        $brandName = $brand->name;
        $description = "{$categoryName} {$brandName} chính hãng tại {$defaultSiteName}. "
            . "Khám phá bộ sưu tập {$categoryName} {$brandName} chất lượng cao, đa dạng mẫu mã. "
            . "Giá tốt, giao hàng nhanh toàn quốc. Bảng giá 2026 cập nhật mới nhất.";
        
        // Keywords: Kết hợp category + brand
        $keywords = "{$categoryName} {$brandName}, {$categoryName} {$brandName} chính hãng, "
            . "bảng giá {$categoryName} {$brandName}, mua {$categoryName} {$brandName}, "
            . "{$defaultSiteName}, thiết bị tự động hóa, giải pháp công nghiệp";
        
        // Image: Ưu tiên brand image, fallback category image, cuối cùng site logo
        $image = null;
        if ($brand->image && file_exists(public_path($brand->image))) {
            $image = asset($brand->image);
        } elseif ($category->image && file_exists(public_path('clients/assets/img/categories/'.$category->image))) {
            $image = asset('clients/assets/img/categories/'.$category->image);
        } else {
            $image = asset('clients/assets/img/business/'.($settings->site_banner ?? $settings->site_logo));
        }
        
        // Nếu có keyword search, điều chỉnh title và description
        if ($keyword !== '') {
            $title = "Tìm kiếm \"{$keyword}\" trong {$categoryName} {$brandName} - {$defaultSiteName}";
            $description = "Kết quả tìm kiếm \"{$keyword}\" trong {$categoryName} {$brandName} tại {$defaultSiteName}. "
                . "Đa dạng mẫu mã, chất lượng tốt, giá ưu đãi.";
        }
        
        return [
            'title' => $title,
            'description' => $description,
            'keywords' => $keywords,
            'canonical' => $canonicalUrl,
            'image' => $image,
        ];
    }

    protected function findProductBySku(string $keyword): ?Product
    {
        return Product::active()
            ->whereRaw('LOWER(sku) = ?', [strtolower($keyword)])
            ->first();
    }

    /**
     * Sanitize keyword input - chống XSS, SQL injection, và các ký tự không hợp lệ
     */
    protected function sanitizeKeyword(?string $keyword): string
    {
        if (! $keyword) {
            return '';
        }

        // Loại bỏ HTML tags và script tags
        $keyword = strip_tags($keyword);
        
        // Loại bỏ các ký tự đặc biệt có thể dùng cho SQL injection hoặc XSS
        // Chỉ giữ lại: chữ cái (Unicode), số, khoảng trắng, dấu gạch ngang
        $keyword = preg_replace('/[^\p{L}\p{N}\s\-]/u', '', $keyword);
        
        // Chuẩn hóa khoảng trắng (nhiều space thành 1 space)
        $keyword = preg_replace('/\s+/u', ' ', $keyword);
        
        // Giới hạn độ dài tối đa 100 ký tự để tránh DoS
        $keyword = mb_substr($keyword, 0, 100);
        
        // Loại bỏ các từ khóa SQL injection phổ biến (case insensitive)
        $sqlKeywords = ['union', 'select', 'insert', 'update', 'delete', 'drop', 'create', 'alter', 'exec', 'execute'];
        $words = explode(' ', mb_strtolower($keyword));
        $words = array_filter($words, function ($word) use ($sqlKeywords) {
            return ! in_array(trim($word), $sqlKeywords, true);
        });
        $keyword = implode(' ', $words);

        return trim($keyword);
    }

    /**
     * Sanitize slug input - chống XSS, SQL injection, và validate format
     */
    protected function sanitizeSlug(?string $slug): ?string
    {
        if (! $slug) {
            return null;
        }

        // Loại bỏ HTML tags
        $slug = strip_tags($slug);
        
        // Giới hạn độ dài tối đa 100 ký tự
        $slug = mb_substr($slug, 0, 100);
        
        // Chuyển thành slug format (chỉ a-z, 0-9, -)
        $slug = Str::slug($slug);
        
        // Validate format: chỉ cho phép a-z, 0-9, - (không có ký tự đặc biệt)
        if (! preg_match('/^[a-z0-9\-]+$/', $slug)) {
            return null;
        }
        
        // Loại bỏ các slug có chứa từ khóa SQL injection
        $sqlKeywords = ['union', 'select', 'insert', 'update', 'delete', 'drop', 'create', 'alter', 'exec', 'execute'];
        if (in_array(strtolower($slug), $sqlKeywords, true)) {
            return null;
        }

        return $slug === '' ? null : $slug;
    }

    protected function priceExpression(): string
    {
        return 'COALESCE(NULLIF(sale_price, 0), price)';
    }
}
