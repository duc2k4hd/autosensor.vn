<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Image;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Tag;
use App\Services\ActivityLogService;
use App\Services\Admin\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService,
        protected ActivityLogService $activityLogService
    ) {}

    public function index(Request $request): View
    {
        $perPage = $request->integer('per_page', 20);
        if (!in_array($perPage, [20, 100, 500, 1000, 2000, 5000])) {
            $perPage = 20;
        }

        // Tối ưu RAM: Chỉ lấy các cột cần thiết cho bảng danh sách
        $products = Product::query()
            ->select([
                'id', 'sku', 'name', 'price', 'sale_price', 
                'stock_quantity', 'is_active', 'primary_category_id', 
                'image_ids', 'slug'
            ])
            ->with(['primaryCategory' => function ($query) {
                $query->select('id', 'name', 'slug');
            }])
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $keyword = trim($request->keyword);
                if (strlen($keyword) <= 10) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('name', 'like', "{$keyword}%")
                            ->orWhere('sku', 'like', "{$keyword}%");
                    });
                } else {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%")
                            ->orWhere('sku', 'like', "%{$keyword}%");
                    });
                }
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                if ($request->status === 'active') {
                    $query->where('is_active', true);
                } elseif ($request->status === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->when($request->filled('category_id'), function ($query) use ($request) {
                $query->where('primary_category_id', $request->category_id);
            })
            ->when($request->filled('brand_id'), function ($query) use ($request) {
                $query->where('brand_id', $request->brand_id);
            })
            ->when($request->filled('sort_by'), function ($query) use ($request) {
                switch ($request->sort_by) {
                    case 'price_asc':
                        $query->orderBy('price', 'asc');
                        break;
                    case 'price_desc':
                        $query->orderBy('price', 'desc');
                        break;
                    case 'name_asc':
                        $query->orderBy('name', 'asc');
                        break;
                    case 'name_desc':
                        $query->orderBy('name', 'desc');
                        break;
                    default:
                        $query->orderByDesc('id');
                        break;
                }
            }, function ($query) {
                $query->orderByDesc('id');
            })
            ->paginate($perPage)
            ->appends($request->query());

        // Cache danh mục để hiển thị trong bộ lọc
        $categories = Cache::remember('admin_categories_filter_list', now()->addHours(6), function () {
            return Category::where('is_active', true)
                ->orderBy('name')
                ->select('id', 'name')
                ->get();
        });

        // Cache hãng sản xuất để hiển thị trong bộ lọc
        $brands = Cache::remember('admin_brands_filter_list', now()->addHours(6), function () {
            return Brand::where('is_active', true)
                ->orderBy('name')
                ->select('id', 'name')
                ->get();
        });

        // Preload images để tránh N+1 query
        Product::preloadImages($products->items());

        return view('admins.products.index', compact('products', 'categories', 'brands'));
    }

    public function create(): View
    {
        // Cache categories, brands, tags để tránh load lại mỗi lần vào trang
        // Cache 1 ngày vì dữ liệu này ít thay đổi
        $categories = Cache::remember('admin_categories_active', now()->addDay(), function () {
            return Category::where('is_active', true)->orderBy('name')->get();
        });

        $brands = Cache::remember('admin_brands_active', now()->addDay(), function () {
            return Brand::where('is_active', true)->orderBy('name')->get();
        });

        // Tags: không load tất cả, sẽ load qua autocomplete khi user search
        $product = new Product;
        $product->load('allVariants');

        return view('admins.products.form', [
            'product' => $product,
            'categories' => $categories,
            'brands' => $brands,
            'tags' => collect(), // Empty collection, sẽ load qua autocomplete
            'mediaImages' => [],
            'siteUrl' => $this->getSiteUrl(),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        try {
            $product = $this->productService->create($request->validated());

            // Log activity
            $this->activityLogService->logCreate($product, 'Tạo sản phẩm mới: '.$product->name);

            return redirect()
                ->route('admin.products.edit', $product)
                ->with('success', 'Tạo sản phẩm thành công');
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', 'Không thể tạo sản phẩm: '.$e->getMessage());
        }
    }

    public function show(Product $product): View
    {
        $product->load(['primaryCategory', 'faqs', 'howTos']);

        return view('admins.products.show', compact('product'));
    }

    public function edit(Product $product): RedirectResponse|View
    {
        if ($response = $this->handleEditingLock($product, true)) {
            return $response;
        }

        // Load images từ image_ids
        $product->load(['primaryCategory', 'faqs', 'howTos', 'allVariants']);

        // Cache categories, brands, tags để tránh load lại mỗi lần vào trang
        // Cache 1 ngày vì dữ liệu này ít thay đổi
        $categories = Cache::remember('admin_categories_active', now()->addDay(), function () {
            return Category::where('is_active', true)->orderBy('name')->get();
        });

        $brands = Cache::remember('admin_brands_active', now()->addDay(), function () {
            return Brand::where('is_active', true)->orderBy('name')->get();
        });

        // Tags: không load tất cả, sẽ load qua autocomplete khi user search
        // Chỉ load tags đã được gán cho product này
        $productTags = collect();
        if ($product->tag_ids && is_array($product->tag_ids)) {
            $productTags = Tag::whereIn('id', $product->tag_ids)
                ->where('entity_type', Product::class)
                ->select('id', 'name')
                ->get();
        }

        return view('admins.products.form', [
            'product' => $product,
            'categories' => $categories,
            'brands' => $brands,
            'tags' => $productTags, // Chỉ tags đã được gán
            'mediaImages' => [],
            'siteUrl' => $this->getSiteUrl(),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        if ($response = $this->handleEditingLock($product, false)) {
            return $response;
        }

        try {
            $oldData = $product->toArray();
            $this->productService->update($product, $request->validated());
            $this->releaseEditingLock($product);

            // Log activity
            $this->activityLogService->logUpdate($product->fresh(), $oldData, 'Cập nhật sản phẩm: '.$product->name);

            return redirect()
                ->route('admin.products.edit', $product)
                ->with('success', 'Cập nhật sản phẩm thành công');
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', 'Không thể cập nhật: '.$e->getMessage());
        }
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($response = $this->handleEditingLock($product, false)) {
            return $response;
        }

        // Log activity before delete
        $this->activityLogService->logDelete($product, 'Ẩn sản phẩm (xóa mềm): '.$product->name);

        $this->releaseEditingLock($product);
        $this->productService->delete($product);

        return back()
            ->with('success', 'Đã chuyển sản phẩm sang trạng thái tạm ẩn');
    }

    public function forceDelete(Product $product): RedirectResponse
    {
        if ($response = $this->handleEditingLock($product, false)) {
            return $response;
        }

        // Log activity before force delete
        $this->activityLogService->logDelete($product, 'Xóa vĩnh viễn sản phẩm: '.$product->name);

        $this->releaseEditingLock($product);
        $this->productService->forceDelete($product);

        return back()
            ->with('success', 'Đã xóa vĩnh viễn sản phẩm: ' . $product->name);
    }

    public function restore(Request $request, Product $product): RedirectResponse
    {
        try {
            // Khôi phục sản phẩm bằng cách set is_active = true
            $product->update(['is_active' => true]);

            return back()
                ->with('success', 'Đã khôi phục sản phẩm (đang ở trạng thái tạm ẩn, cần bật Đang bán nếu muốn hiển thị).');
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', 'Không thể khôi phục: '.$e->getMessage());
        }
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        // Hỗ trợ cả array (old way) và string (new way để tránh max_input_vars)
        if ($request->filled('selected_ids')) {
            $productIds = explode(',', $request->selected_ids);
            $productIds = array_filter(array_map('intval', $productIds));
        } else {
            $productIds = $request->input('selected', []);
        }

        if (empty($productIds)) {
            return back()->with('error', 'Vui lòng chọn ít nhất một sản phẩm.');
        }

        $action = $request->input('bulk_action');

        if ($action === 'hide' || $action === 'delete') {
            // 'hide' và 'delete' hiện tại đều là xóa mềm (is_active = false) và xóa bình luận
            $this->productService->bulkDelete($productIds);

            return back()->with('success', 'Đã chuyển ' . count($productIds) . ' sản phẩm sang trạng thái tạm ẩn và xóa bình luận.');
        }

        if ($action === 'force_delete') {
            $this->productService->bulkForceDelete($productIds);

            return back()->with('success', 'Đã xóa vĩnh viễn ' . count($productIds) . ' sản phẩm.');
        }

        if ($action === 'restore') {
            Product::whereIn('id', $productIds)->update(['is_active' => true]);

            return back()->with('success', 'Đã khôi phục ' . count($productIds) . ' sản phẩm.');
        }

        return back()->with('error', 'Hành động không hợp lệ.');
    }

    public function inventory(Product $product): View
    {
        $movements = InventoryMovement::query()
            ->with('account')
            ->where('product_id', $product->id)
            ->orderByDesc('created_at')
            ->paginate(30);

        return view('admins.products.inventory', compact('product', 'movements'));
    }

    public function inventoryAdjust(Product $product, \Illuminate\Http\Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:increase,decrease,set'],
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $current = (int) ($product->stock_quantity ?? 0);
        $qty = (int) $data['quantity'];

        if ($data['action'] === 'set') {
            $change = $qty - $current;
            $type = 'adjust';
        } elseif ($data['action'] === 'increase') {
            $change = $qty;
            $type = 'import';
        } else {
            $change = -$qty;
            $type = 'export';
        }

        try {
            /** @var \App\Models\Account|null $actor */
            $actor = auth('admin')->user() ?? auth('web')->user();

            app(\App\Services\InventoryService::class)->adjustStock(
                $product,
                $change,
                $type,
                $actor,
                null,
                null,
                $data['note'] ?? null
            );

            return redirect()
                ->route('admin.products.inventory', $product)
                ->with('success', 'Đã cập nhật tồn kho sản phẩm.');
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Không thể cập nhật tồn kho: '.$e->getMessage());
        }
    }

    /**
     * Release lock via API (khi đóng trang hoặc navigate away)
     */
    public function releaseLock(Product $product): JsonResponse
    {
        $this->releaseEditingLock($product);

        return response()->json([
            'success' => true,
            'message' => 'Lock đã được release thành công',
        ]);
    }

    /**
     * API endpoint để load thêm ảnh (pagination)
     */
    public function getMediaImagesApi(): JsonResponse
    {
        // Giảm limit mặc định từ 100 xuống 30 để tránh load quá nhiều ảnh cùng lúc
        // Frontend có thể load thêm bằng cách tăng offset
        $limit = min((int) request('limit', 30), 100); // Max 100, default 30
        $offset = (int) request('offset', 0);
        $search = request('search');
        $folder = request('folder'); // Thêm tham số folder

        $result = $this->getMediaImages($limit, $offset, $search, $folder);

        return response()->json($result);
    }

    /**
     * API endpoint để search tags (autocomplete)
     * Chỉ trả về tags chứa từ khóa đúng hoặc gần đúng
     */
    public function searchTagsApi(Request $request): JsonResponse
    {
        $request->validate([
            'keyword' => 'nullable|string|max:255',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $keyword = trim($request->input('keyword', ''));
        $limit = (int) $request->input('limit', 20);

        $query = Tag::where('entity_type', Product::class)
            ->where('is_active', true)
            ->select('id', 'name')
            ->distinct('name');

        // Nếu có keyword, search tags chứa từ khóa (đúng hoặc gần đúng)
        if (!empty($keyword)) {
            $query->where(function ($q) use ($keyword) {
                // Tìm chính xác từ đầu (ưu tiên)
                $q->where('name', 'like', "{$keyword}%")
                    // Hoặc chứa từ khóa ở giữa
                    ->orWhere('name', 'like', "%{$keyword}%");
            });
        }

        $tags = $query->orderBy('name')
            ->limit($limit)
            ->get()
            ->unique('name')
            ->values()
            ->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $tags,
            'total' => $tags->count(),
        ]);
    }

    /**
     * Upload ảnh đã crop từ TinyMCE editor
     */
    public function uploadCroppedImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:webp,jpg,jpeg,png,gif', 'max:5120'], // 5MB
            'original_filename' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $file = $request->file('image');
            $originalFilename = $request->input('original_filename', 'cropped-image');

            // Extract base name and extension
            $originalBaseName = pathinfo($originalFilename, PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();

            // Get image dimensions
            $imageInfo = getimagesize($file->getRealPath());
            $width = $imageInfo[0];
            $height = $imageInfo[1];

            // Build new filename: baseFilename-size-w-h.extension
            // If filename already contains -size-w-h, replace it; otherwise add it
            $newFilename = preg_replace(
                '/-size-\d+-\d+$/',
                '',
                $originalBaseName
            ).'-size-'.$width.'-'.$height.'.'.$extension;

            // Save to public/clients/assets/img/clothes/
            $destination = public_path('clients/assets/img/clothes');
            if (! is_dir($destination)) {
                mkdir($destination, 0755, true);
            }

            $file->move($destination, $newFilename);

            // Build full URL
            $baseUrl = $this->getSiteUrl();
            $fullUrl = rtrim($baseUrl, '/').'/clients/assets/img/clothes/'.$newFilename;

            return response()->json([
                'success' => true,
                'filename' => $newFilename,
                'url' => $fullUrl,
                'width' => $width,
                'height' => $height,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Không thể upload ảnh: '.$e->getMessage(),
            ], 500);
        }
    }

    private function getMediaImages(int $limit = 30, int $offset = 0, ?string $search = null, ?string $folder = null): array
    {
        $baseUrl = $this->getSiteUrl();
        $imageExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'avif', 'ico'];

        /**
         * Trường hợp chọn folder: chỉ trả về ảnh trong đúng thư mục được chọn.
         * Để tránh quét toàn bộ filesystem, chỉ quét folder cụ thể và paginate trên kết quả đó.
         */
        if (!empty($folder)) {
            $folder = trim($folder, '/');
            $folderPath = public_path('clients/assets/img/'.$folder);

            if (!is_dir($folderPath)) {
                return [
                    'data' => [],
                    'total' => 0,
                    'offset' => $offset,
                    'limit' => $limit,
                    'has_more' => false,
                ];
            }

            // Lấy danh sách file trong folder (không đệ quy) và sort theo thời gian sửa gần nhất (mới nhất trước)
            $filesInFolder = collect(File::files($folderPath))
                ->filter(function ($file) use ($imageExtensions) {
                    return in_array(strtolower($file->getExtension()), $imageExtensions);
                })
                ->when($search && !empty(trim((string) $search)), function ($files) use ($search) {
                    $keyword = trim((string) $search);
                    $keywordNoSpace = preg_replace('/\s+/u', '-', $keyword);
                    $keywordSlug = Str::slug($keyword);
                    $variants = array_values(array_unique(array_filter([
                        $keyword,
                        $keywordNoSpace,
                        $keywordSlug,
                    ], fn ($v) => is_string($v) && trim($v) !== '')));

                    if (empty($variants)) {
                        return $files;
                    }

                    return $files->filter(function ($file) use ($variants) {
                        $name = mb_strtolower($file->getFilename(), 'UTF-8');
                        foreach ($variants as $v) {
                            $vv = mb_strtolower($v, 'UTF-8');
                            if ($vv !== '' && str_contains($name, $vv)) {
                                return true;
                            }
                        }
                        return false;
                    });
                })
                ->sortByDesc(fn ($file) => $file->getMTime())
                ->values();

            $total = $filesInFolder->count();
            $slice = $filesInFolder->slice($offset, $limit);

            // Lấy metadata từ DB cho các file trong slice
            $names = $slice->map(fn ($f) => $f->getFilename())->values()->all();
            $imagesMeta = $names
                ? Image::whereIn('url', $names)->get()->keyBy('url')
                : collect();

            $data = [];
            foreach ($slice as $file) {
                $filename = $file->getFilename();
                $meta = $imagesMeta[$filename] ?? null;

                $data[] = [
                    'name' => $filename,
                    'url' => rtrim($baseUrl, '/').'/clients/assets/img/'.$folder.'/'.$filename,
                    'path' => 'clients/assets/img/'.$folder.'/'.$filename,
                    'relative_path' => $folder.'/'.$filename,
                    'title' => $meta->title ?? null,
                    'alt' => $meta->alt ?? null,
                    'size' => $file->getSize(),
                    'mime_type' => mime_content_type($file->getRealPath()) ?: ('image/'.strtolower($file->getExtension())),
                ];
            }

            return [
                'data' => $data,
                'total' => $total,
                'offset' => $offset,
                'limit' => $limit,
                'has_more' => ($offset + $limit) < $total,
            ];
        }

        /**
         * Trường hợp không chọn folder: dùng DB để paginate, tránh quét toàn bộ filesystem.
         */
        $query = Image::query()
            ->select('id', 'url', 'title', 'alt', 'order')
            ->orderByDesc('id'); // Mới nhất trước

        if ($search && !empty(trim($search))) {
            $keyword = trim((string) $search);
            $keywordNoSpace = preg_replace('/\s+/u', '-', $keyword);
            $keywordSlug = Str::slug($keyword);

            // Tập biến thể để bắt đúng tên file (thường dùng dấu -)
            $variants = array_values(array_unique(array_filter([
                $keyword,
                $keywordNoSpace,
                $keywordSlug,
            ], fn ($v) => is_string($v) && trim($v) !== '')));

            // Lọc theo keyword: ưu tiên match theo url (tên file) trước, sau đó title/alt
            $query->where(function ($q) use ($variants) {
                foreach ($variants as $v) {
                    $q->orWhere('url', 'like', "%{$v}%");
                }
                foreach ($variants as $v) {
                    $q->orWhere('title', 'like', "%{$v}%")
                      ->orWhere('alt', 'like', "%{$v}%");
                }
            });

            // Sắp xếp ưu tiên: url match trước (exact/prefix/contains), rồi mới tới title/alt
            // (giữ orderByDesc('id') làm tie-breaker)
            $query->orderByRaw(
                'CASE
                    WHEN LOWER(url) = LOWER(?) THEN 0
                    WHEN LOWER(url) LIKE LOWER(?) THEN 1
                    WHEN LOWER(url) LIKE LOWER(?) THEN 2
                    WHEN LOWER(title) LIKE LOWER(?) THEN 3
                    WHEN LOWER(alt) LIKE LOWER(?) THEN 4
                    ELSE 10
                END',
                [
                    $variants[0] ?? $keyword,
                    ($variants[0] ?? $keyword).'%',      // startsWith
                    '%'.($variants[0] ?? $keyword).'%',  // contains
                    '%'.($variants[0] ?? $keyword).'%',
                    '%'.($variants[0] ?? $keyword).'%',
                ]
            );
        }

        $total = $query->count();

        $images = $query->offset($offset)
            ->limit($limit)
            ->get();

        $files = [];
        $clothesPath = public_path('clients/assets/img/clothes');

        foreach ($images as $image) {
            $attributes = $image->getAttributes();
            $rawUrl = $attributes['url'] ?? null;
            if (empty($rawUrl)) {
                continue;
            }
            $normalizedUrl = ltrim($rawUrl, '/');
            $normalizedUrl = preg_replace('#^clients/assets/img/clothes/#', '', $normalizedUrl);
            $normalizedUrl = basename($normalizedUrl);

            $filePath = $clothesPath.DIRECTORY_SEPARATOR.$normalizedUrl;

            if (!file_exists($filePath) || !is_file($filePath)) {
                continue;
            }

            $extension = strtolower(pathinfo($normalizedUrl, PATHINFO_EXTENSION));
            $mimeType = 'image/jpeg';
            if (function_exists('mime_content_type')) {
                try {
                    $mimeType = mime_content_type($filePath) ?: 'image/'.($extension ?: 'jpeg');
                } catch (\Throwable $e) {
                    $mimeType = 'image/'.($extension ?: 'jpeg');
                }
            }

            $files[] = [
                'name' => $normalizedUrl,
                'url' => rtrim($baseUrl, '/').'/clients/assets/img/clothes/'.$normalizedUrl,
                'path' => 'clients/assets/img/clothes/'.$normalizedUrl,
                'relative_path' => $normalizedUrl,
                'title' => $image->title,
                'alt' => $image->alt,
                'size' => filesize($filePath),
                'mime_type' => $mimeType,
            ];
        }

        $actualCount = count($files);
        $hasMore = ($actualCount >= $limit) || (($offset + $actualCount) < $total);

        return [
            'data' => $files,
            'total' => $total,
            'offset' => $offset,
            'limit' => $limit,
            'has_more' => $hasMore,
        ];
    }

    /**
     * Parse search terms: tách từng từ ra để tìm rộng
     * Ví dụ: "áo polo nam" -> ["áo", "polo", "nam"]
     * Nếu chỉ có 1 từ thì vẫn trả về mảng có 1 phần tử
     */
    private function parseSearchTerms(string $search): array
    {
        // Loại bỏ khoảng trắng thừa và chuyển sang lowercase
        $search = trim($search);
        if (empty($search)) {
            return [];
        }

        $search = mb_strtolower($search, 'UTF-8');

        // Tách theo khoảng trắng và loại bỏ các từ rỗng
        $terms = preg_split('/\s+/u', $search);
        $terms = array_filter($terms, fn ($term) => ! empty(trim($term)) && mb_strlen(trim($term), 'UTF-8') > 0);

        $result = array_values($terms);

        // Nếu không tách được từ nào (chỉ có 1 từ), trả về chính từ đó
        if (empty($result)) {
            $result = [$search];
        }

        return $result;
    }

    private function getSiteUrl(): string
    {
        $siteUrl = Setting::where('key', 'site_url')->value('value') ?? config('app.url');
        if (! $siteUrl) {
            $siteUrl = config('app.url');
        }

        return rtrim($siteUrl, '/');
    }

    protected function handleEditingLock(Product $product, bool $acquireLock = true): ?RedirectResponse
    {
        $currentUser = auth('web')->user();
        $lockTtl = now()->subMinutes((int) config('app.editor_lock_minutes', 15));

        $product->loadMissing('lockedByUser');

        // Tự động release lock nếu đã hết hạn
        if ($product->locked_by && $product->locked_at) {
            if ($product->locked_at->lessThanOrEqualTo($lockTtl)) {
                $product->forceFill([
                    'locked_by' => null,
                    'locked_at' => null,
                ])->save();
                $product->refresh();
            }
        }

        // Nếu lock là của chính user hiện tại, LUÔN cho phép
        if ($product->locked_by && (int) $product->locked_by === (int) $currentUser->id) {
            if ($acquireLock) {
                $product->forceFill([
                    'locked_by' => $currentUser->id,
                    'locked_at' => now(),
                ])->save();
            }

            return null;
        }

        // Kiểm tra lock còn hiệu lực và không phải của user hiện tại
        if ($product->locked_by && (int) $product->locked_by !== (int) $currentUser->id) {
            $lockedAt = $product->locked_at;
            if ($lockedAt && $lockedAt->greaterThan($lockTtl)) {
                $lockedBy = optional($product->lockedByUser)->name ?? 'người dùng khác';

                return redirect()
                    ->route('admin.products.index')
                    ->with('error', "Sản phẩm đang được {$lockedBy} chỉnh sửa. Vui lòng thử lại sau vài phút.");
            }
        }

        // Tạo lock mới nếu cần (khi vào trang edit)
        if ($acquireLock) {
            $product->forceFill([
                'locked_by' => $currentUser->id,
                'locked_at' => now(),
            ])->save();
        }

        return null;
    }

    protected function releaseEditingLock(Product $product): void
    {
        if ($product->locked_by && $product->locked_by === auth('web')->id()) {
            $product->forceFill([
                'locked_by' => null,
                'locked_at' => null,
            ])->save();
        }
    }
}
