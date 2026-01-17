<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\Image;
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

        return view('admins.media.index', [
            'scope' => $scope,
            'folder' => $folder,
        ]);
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
        
        // Cố định 50 items mỗi page để tối ưu performance
        $perPage = 50;

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
            'scope' => 'required|in:admin,client',
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
            'scope' => 'required|in:admin,client',
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
            'scope' => 'required|in:admin,client',
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
            'scope' => 'required|in:admin,client',
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
            'scope' => 'required|in:admin,client',
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
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        if (! $this->permissionService->can('delete')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'paths' => 'required|array',
            'paths.*' => 'required|string',
            'scope' => 'required|in:admin,client',
        ]);

        $paths = $request->get('paths', []);
        $scope = $request->get('scope');
        $successCount = 0;
        $failedPaths = [];

        foreach ($paths as $path) {
            try {
                $success = $this->mediaService->deleteFile($path, $scope);
                if ($success) {
                    $successCount++;
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
            'scope' => 'required|in:admin,client',
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
            'scope' => 'required|in:admin,client',
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
            'scope' => 'required|in:admin,client',
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
            'scope' => 'required|in:admin,client',
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
            'scope' => 'required|in:admin,client',
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
            'scope' => 'required|in:admin,client',
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
        return $scope === 'admin' ? 'admins/img' : 'clients/assets/img';
    }
}
