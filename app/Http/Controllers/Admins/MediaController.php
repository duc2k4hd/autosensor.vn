<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Product;
use App\Services\Media\DirectoryService;
use App\Services\Media\MediaService;
use App\Services\Media\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    protected MediaService $mediaService;

    protected DirectoryService $directoryService;

    protected PermissionService $permissionService;

    public function __construct(
        MediaService $mediaService,
        DirectoryService $directoryService,
        PermissionService $permissionService
    ) {
        $this->mediaService = $mediaService;
        $this->directoryService = $directoryService;
        $this->permissionService = $permissionService;
    }

    /**
     * Index page - Media Manager UI
     */
    public function index(Request $request)
    {
        $scope = $request->get('scope', 'admin');
        $folder = $request->get('folder', '');

        if (! $this->permissionService->can('view')) {
            abort(403, 'Unauthorized');
        }

        $downloadCategories = Category::active()
            ->orderBy('name')
            ->get(['id', 'name']);

        $downloadBrands = Brand::active()
            ->ordered()
            ->get(['id', 'name']);

        return view('admins.media.index', [
            'scope' => $scope,
            'folder' => $folder,
            'downloadCategories' => $downloadCategories,
            'downloadBrands' => $downloadBrands,
        ]);
    }

    /**
     * Download all images related to selected categories / brands as a ZIP file.
     */
    public function downloadZip(Request $request)
    {
        if (! $this->permissionService->can('view')) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'type' => 'required|in:category,brand',
            'ids' => 'required|array',
            'ids.*' => 'integer|min:1',
        ]);

        $type = $validated['type'];
        $ids = $validated['ids'];

        $paths = collect();

        // Lấy tất cả products thuộc danh mục / hãng đã chọn
        if ($type === 'category') {
            $products = Product::inCategory($ids)->get();

            Log::debug('downloadZip: products by category', [
                'category_ids' => $ids,
                'products_count' => $products->count(),
            ]);
        } else {
            $products = Product::whereIn('brand_id', $ids)->get();

            Log::debug('downloadZip: products by brand', [
                'brand_ids' => $ids,
                'products_count' => $products->count(),
            ]);
        }

        // Preload images for all products via HasImageIds trait to avoid N+1
        Product::preloadImages($products);

        foreach ($products as $product) {
            $images = $product->images ?? collect();

            Log::debug('downloadZip: product images', [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'images_count' => $images->count(),
                'image_ids' => $product->image_ids,
            ]);

            foreach ($images as $image) {
                if (empty($image->url)) {
                    continue;
                }

                // Theo comment trong Image::getUrlAttribute, url là basename
                $relativePath = 'clients/assets/img/clothes/' . $image->url;
                $paths->push($relativePath);
            }
        }

        $paths = $paths
            ->filter(fn ($path) => ! empty($path))
            ->unique()
            ->values();

        if ($paths->isEmpty()) {
            Log::warning('Media downloadZip: no paths found', [
                'type' => $type,
                'ids' => $ids,
            ]);
            return back()->with('error', 'Không tìm thấy ảnh nào để tải cho lựa chọn này.');
        }

        $zipFileName = 'media-'.$type.'-'.now()->format('Ymd-His').'.zip';
        $tmpDir = storage_path('app/tmp');

        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }

        $tmpPath = $tmpDir.DIRECTORY_SEPARATOR.$zipFileName;

        $zip = new \ZipArchive();
        if ($zip->open($tmpPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Không thể tạo file ZIP.');
        }

        foreach ($paths as $path) {
            // Cho phép cả URL đầy đủ lẫn path tương đối
            $relative = parse_url($path, PHP_URL_PATH) ?? $path;
            $relative = ltrim($relative, '/');
            $fullPath = public_path($relative);

            if (is_file($fullPath)) {
                $zip->addFile($fullPath, basename($fullPath));
            } else {
                Log::debug('downloadZip: file not found on disk', [
                    'relative' => $relative,
                    'full_path' => $fullPath,
                ]);
            }
        }

        $zip->close();

        return response()->download($tmpPath, $zipFileName)->deleteFileAfterSend(true);
    }

    /**
     * List files and folders
     */
    public function list(Request $request): JsonResponse
    {
        if (! $this->permissionService->can('view')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $scope = $request->get('scope', 'admin');
        $folder = (string) ($request->get('folder', '') ?? '');
        $filters = $request->only(['extension', 'min_size', 'max_size', 'orientation']);
        $page = max(1, (int) $request->get('page', 1));
        $search = trim((string) $request->get('search', ''));
        
        // Cho phép chọn số lượng items mỗi page, mặc định 100
        $allowedPerPage = [100, 200, 500, 2000];
        $perPage = (int) $request->get('per_page', 100);
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 100; // Mặc định nếu giá trị không hợp lệ
        }

        Log::debug('MediaController list', [
            'scope' => $scope,
            'folder' => $folder,
            'page' => $page,
            'per_page' => $perPage,
            'search' => $search,
        ]);

        try {
            // ✅ PAGINATION Ở SOURCE: chỉ load 50 files cho page hiện tại
            $result = $this->directoryService->listFiles(
                $folder, 
                $scope, 
                $filters,
                $page,
                $perPage,
                $search // Search đã được xử lý ở DirectoryService
            );
            
            $files = $result['files'] ?? [];
            $total = $result['total'] ?? 0;
            $hasMore = $result['has_more'] ?? false;
            
            // ✅ CACHE folder tree (1 giờ)
            $folders = Cache::remember(
                "media_folders_{$scope}_{$folder}",
                3600,
                fn() => $this->directoryService->getFolderTree($scope, $folder)
            );
        } catch (\Throwable $e) {
            Log::error('Media list error', [
                'error' => $e->getMessage(),
                'scope' => $scope,
                'folder' => $folder,
            ]);

            return response()->json([
                'files' => [],
                'folders' => [],
                'error' => $e->getMessage(),
            ], 500);
        }

        // ✅ CHỈ load images meta cho 50 files đang hiển thị (không phải tất cả)
        $fileNames = collect($files)->pluck('filename')->filter()->values();
        $imagesMetaMap = [];
        if ($fileNames->isNotEmpty()) {
            // Chỉ query cho 50 filenames này thôi
            $imagesMeta = Image::whereIn('url', $fileNames->toArray())
                ->select('url', 'title', 'alt')
                ->get()
                ->keyBy('url');
            
            foreach ($imagesMeta as $url => $image) {
                $imagesMetaMap[$url] = $image;
            }
            unset($imagesMeta);
        }

        // Attach meta vào files
        foreach ($files as &$file) {
            $name = $file['filename'] ?? null;
            if ($name && isset($imagesMetaMap[$name])) {
                $file['title'] = $imagesMetaMap[$name]->title ?? null;
                $file['alt'] = $imagesMetaMap[$name]->alt ?? null;
            }
        }
        unset($file, $imagesMetaMap);

        // Append URLs cho files
        $paginated = collect($files)
            ->map(fn ($file) => $this->appendFileUrls($file, $scope))
            ->all();

        return response()->json([
            'files' => $paginated,
            'folders' => $folders,
            'current_folder' => $folder,
            'scope' => $scope,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'has_more' => $hasMore,
            ],
        ]);
    }

    /**
     * Upload files
     */
    public function upload(Request $request): JsonResponse
    {
        if (! $this->permissionService->can('upload')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'files' => 'required|array',
            'files.*' => 'file|max:10240', // 10MB max
            'folder' => 'required|string', // Bắt buộc chọn folder, không được rỗng
            'scope' => 'required|in:admin,client,catalog',
        ]);

        $files = $request->file('files');
        $folder = trim((string) ($request->get('folder', '') ?? ''));
        if ($folder === '') {
            return response()->json([
                'success' => false,
                'error' => 'Vui lòng chọn thư mục lưu trữ (folder) trước khi upload.',
            ], 422);
        }
        $scope = $request->get('scope', 'admin');

        // Normalize folder path
        $folder = trim($folder, '/\\');
        $folder = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $folder);

        Log::debug('MediaController upload', [
            'scope' => $scope,
            'folder' => $folder,
            'files_count' => count($files),
        ]);

        try {
            $results = $this->mediaService->uploadFiles($files, $folder, $scope);
            $filesWithUrl = collect($results)->map(function ($result) use ($scope) {
                if (($result['success'] ?? false) && isset($result['path'])) {
                    $result = $this->appendFileUrls($result, $scope);
                }

                return $result;
            })->all();

            // Check if all uploads succeeded
            $allSuccess = collect($results)->every(fn ($result) => ($result['success'] ?? false) === true);

            if (! $allSuccess) {
                Log::warning('Media upload: Some files failed', [
                    'results' => $results,
                ]);
            }

            return response()->json([
                'success' => $allSuccess,
                'files' => $filesWithUrl,
                'message' => $allSuccess ? 'Upload thành công' : 'Một số file upload thất bại',
            ]);
        } catch (\Throwable $e) {
            Log::error('Media upload error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'scope' => $scope,
                'folder' => $folder,
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Rename file
     */
    public function rename(Request $request): JsonResponse
    {
        if (! $this->permissionService->can('edit')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'path' => 'required|string',
            'new_name' => 'required|string|max:255',
            'scope' => 'required|in:admin,client,catalog',
        ]);

        try {
            $result = $this->mediaService->renameFile(
                $request->get('path'),
                $request->get('new_name'),
                $request->get('scope')
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Move file
     */
    public function move(Request $request): JsonResponse
    {
        if (! $this->permissionService->can('edit')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'path' => 'required|string',
            'target_folder' => 'required|string',
            'scope' => 'required|in:admin,client,catalog',
        ]);

        try {
            $result = $this->mediaService->moveFile(
                $request->get('path'),
                $request->get('target_folder'),
                $request->get('scope')
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Copy file
     */
    public function copy(Request $request): JsonResponse
    {
        if (! $this->permissionService->can('edit')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'path' => 'required|string',
            'target_folder' => 'required|string',
            'scope' => 'required|in:admin,client,catalog',
        ]);

        try {
            $result = $this->mediaService->copyFile(
                $request->get('path'),
                $request->get('target_folder'),
                $request->get('scope')
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete file
     */
    public function delete(Request $request): JsonResponse
    {
        if (! $this->permissionService->can('delete')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'path' => 'required|string',
            'scope' => 'required|in:admin,client,catalog',
        ]);

        try {
            $success = $this->mediaService->deleteFile(
                $request->get('path'),
                $request->get('scope')
            );

            if (! $success) {
                return response()->json([
                    'success' => false,
                    'error' => 'File not found or could not be deleted',
                ], 404);
            }

            return response()->json([
                'success' => true,
            ]);
        } catch (\Throwable $e) {
            Log::error('Media delete error', [
                'error' => $e->getMessage(),
                'path' => $request->get('path'),
                'scope' => $request->get('scope'),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk delete files
     * Tối ưu: Xóa files trước, sau đó update database một lần cho tất cả (đặc biệt cho catalog)
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        if (! $this->permissionService->can('delete')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'paths' => 'required|array',
            'paths.*' => 'required|string',
            'scope' => 'required|in:admin,client,catalog',
        ]);

        $paths = $request->get('paths', []);
        $scope = $request->get('scope');
        $successCount = 0;
        $failedPaths = [];
        
        // Collect thông tin các catalog files đã xóa để update database một lần
        $deletedCatalogFiles = [];

        // Bước 1: Xóa tất cả files từ filesystem (nhanh)
        foreach ($paths as $path) {
            try {
                $success = $this->mediaService->deleteFileWithoutCatalogUpdate($path, $scope);
                if ($success) {
                    $successCount++;
                    // Lưu thông tin catalog file đã xóa (tính toán từ path, không cần getFileInfo)
                    if ($scope === 'catalog') {
                        // Normalize path để lấy relativePath
                        $normalizedPath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, trim($path, '/\\'));
                        $catalogMarker = 'clients'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'catalog';
                        $markerPos = stripos($normalizedPath, $catalogMarker);
                        if ($markerPos !== false) {
                            $relativePath = substr($normalizedPath, $markerPos + strlen($catalogMarker));
                            $relativePath = trim($relativePath, DIRECTORY_SEPARATOR);
                            $relativePath = str_replace('\\', '/', $relativePath);
                        } else {
                            $relativePath = str_replace('\\', '/', $normalizedPath);
                        }
                        
                        $deletedCatalogFiles[] = [
                            'relativePath' => $relativePath,
                            'filename' => basename($path),
                        ];
                    }
                } else {
                    $failedPaths[] = $path;
                }
            } catch (\Throwable $e) {
                Log::error('Media bulk delete error', [
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);
                $failedPaths[] = $path;
            }
        }

        // Bước 2: Update database một lần cho tất cả catalog files đã xóa (nhanh hơn nhiều)
        if ($scope === 'catalog' && !empty($deletedCatalogFiles)) {
            try {
                $this->mediaService->removeMultipleCatalogsFromProducts($deletedCatalogFiles);
            } catch (\Throwable $e) {
                Log::error('Media bulk delete: Failed to update products', [
                    'error' => $e->getMessage(),
                    'deleted_files_count' => count($deletedCatalogFiles),
                ]);
                // Không fail toàn bộ, chỉ log lỗi
            }
        }

        return response()->json([
            'success' => $successCount > 0,
            'deleted_count' => $successCount,
            'failed_count' => count($failedPaths),
            'failed_paths' => $failedPaths,
        ]);
    }

    /**
     * Get file info
     */
    public function info(Request $request): JsonResponse
    {
        if (! $this->permissionService->can('view')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'path' => 'required|string',
            'scope' => 'required|in:admin,client,catalog',
        ]);

        $info = $this->mediaService->getFileInfo(
            $request->get('path'),
            $request->get('scope')
        );

        if (! $info) {
            return response()->json([
                'success' => false,
                'error' => 'File not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $info,
        ]);
    }

    /**
     * Cập nhật alt/title cho ảnh (dựa trên filename/url đã tồn tại trong bảng images)
     */
    public function updateMeta(Request $request): JsonResponse
    {
        if (! $this->permissionService->can('edit')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'path' => 'required|string',
            'alt' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'scope' => 'required|in:admin,client,catalog',
        ]);

        $path = $request->string('path')->value();
        // Lấy filename từ path (có thể là full URL hoặc relative path)
        $filename = basename($path);

        // Nếu path chứa URL đầy đủ, chỉ lấy tên file
        if (str_contains($filename, '?')) {
            $filename = explode('?', $filename)[0];
        }

        // Tìm hoặc tạo Image record
        $image = Image::firstOrNew(['url' => $filename]);

        // Nếu là record mới, set các giá trị mặc định
        if (! $image->exists) {
            $image->notes = null;
            $image->is_primary = false;
            $image->order = 0;
        }

        // Cập nhật alt và title
        $image->alt = $request->input('alt', '');
        $image->title = $request->input('title', '');
        $image->save();

        return response()->json([
            'success' => true,
            'data' => [
                'url' => $image->url,
                'alt' => $image->alt,
                'title' => $image->title,
            ],
        ]);
    }

    /**
     * Create folder
     */
    public function createFolder(Request $request): JsonResponse
    {
        if (! $this->permissionService->can('manage_folders')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'path' => 'nullable|string',
            'name' => 'required|string|max:255',
            'scope' => 'required|in:admin,client,catalog',
        ]);

        try {
            $result = $this->directoryService->createFolder(
                $request->get('path', ''),
                $request->get('name'),
                $request->get('scope')
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Rename folder
     */
    public function renameFolder(Request $request): JsonResponse
    {
        if (! $this->permissionService->can('manage_folders')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'path' => 'required|string',
            'new_name' => 'required|string|max:255',
            'scope' => 'required|in:admin,client,catalog',
        ]);

        try {
            $result = $this->directoryService->renameFolder(
                $request->get('path'),
                $request->get('new_name'),
                $request->get('scope')
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete folder
     */
    public function deleteFolder(Request $request): JsonResponse
    {
        if (! $this->permissionService->can('manage_folders')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'path' => 'required|string',
            'scope' => 'required|in:admin,client,catalog',
            'force' => 'nullable|boolean',
        ]);

        try {
            $result = $this->directoryService->deleteFolder(
                $request->get('path'),
                $request->get('scope'),
                $request->get('force', false)
            );

            return response()->json([
                'success' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Search files
     */
    public function search(Request $request): JsonResponse
    {
        if (! $this->permissionService->can('view')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'query' => 'nullable|string',
            'scope' => 'required|in:admin,client,catalog',
            'folder' => 'nullable|string',
            'extension' => 'nullable|string',
            'min_size' => 'nullable|integer',
            'max_size' => 'nullable|integer',
        ]);

        $filters = $request->only(['extension', 'min_size', 'max_size']);
        $scope = $request->get('scope', 'admin');
        $folder = $request->get('folder', '');
        $query = $request->get('query', '');

        $files = $this->directoryService->listFiles($folder, $scope, $filters);

        // Filter by search query
        if (! empty($query)) {
            $files = array_filter($files, function ($file) use ($query) {
                return stripos($file['filename'], $query) !== false;
            });
        }

        return response()->json([
            'success' => true,
            'files' => array_values($files),
        ]);
    }

    /**
     * Get folder tree
     */
    public function folderTree(Request $request): JsonResponse
    {
        if (! $this->permissionService->can('view')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $scope = $request->get('scope', 'admin');
        $tree = $this->directoryService->getFolderTree($scope);

        return response()->json([
            'success' => true,
            'tree' => $tree,
        ]);
    }

    private function appendFileUrls(array $file, string $scope): array
    {
        $publicPrefix = $this->getScopePublicPrefix($scope);
        $relativePath = ltrim($file['path'] ?? '', '/');

        if (! empty($relativePath)) {
            // Trả về đường dẫn tương đối, không kèm domain/protocol
            $file['url'] = '/'.$publicPrefix.'/'.$relativePath;
        }

        if (! empty($file['thumbnail_path'])) {
            $file['thumbnail_url'] = '/'.$publicPrefix.'/'.ltrim($file['thumbnail_path'], '/');
        } elseif (! empty($file['url'])) {
            $file['thumbnail_url'] = $file['url'];
        }

        return $file;
    }

    private function getScopePublicPrefix(string $scope): string
    {
        return match ($scope) {
            'admin' => 'admins/img',
            'client' => 'clients/assets/img',
            'catalog' => 'clients/assets/catalog',
            default => 'admins/img',
        };
    }
}
