<?php

namespace App\Services\Media;

use App\Models\Image;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MediaService
{
    protected string $adminRoot;

    protected string $clientRoot;

    protected string $catalogRoot;

    protected ThumbnailService $thumbnailService;

    protected PermissionService $permissionService;

    protected FileManager $fileManager;

    public function __construct(
        ThumbnailService $thumbnailService,
        PermissionService $permissionService,
        FileManager $fileManager
    ) {
        $this->adminRoot = public_path('admins/img');
        $this->clientRoot = public_path('clients/assets/img');
        $this->catalogRoot = public_path('clients/assets/catalog');
        $this->thumbnailService = $thumbnailService;
        $this->permissionService = $permissionService;
        $this->fileManager = $fileManager;
    }

    /**
     * Get root path based on scope
     */
    public function getRootPath(string $scope = 'admin'): string
    {
        return match ($scope) {
            'admin' => $this->adminRoot,
            'client' => $this->clientRoot,
            'catalog' => $this->catalogRoot,
            default => $this->adminRoot,
        };
    }

    /**
     * Upload multiple files
     */
    public function uploadFiles(array $files, string $folder, string $scope = 'admin'): array
    {
        $results = [];
        $rootPath = $this->getRootPath($scope);
        $originalFolder = $folder;

        // Normalize rootPath để đảm bảo dùng đúng DIRECTORY_SEPARATOR
        $rootPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rootPath);
        $rootPath = rtrim($rootPath, DIRECTORY_SEPARATOR);

        // Normalize folder path - chỉ lấy tên folder, không có path đầy đủ
        $folder = trim($folder, '/\\');
        $folder = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $folder);

        if ($folder === '') {
            throw new \InvalidArgumentException('Folder không được để trống. Vui lòng chọn thư mục lưu trữ.');
        }

        // Remove any full path prefixes if accidentally included
        $rootNormalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rootPath);
        if (str_starts_with($folder, $rootNormalized)) {
            $folder = str_replace($rootNormalized.DIRECTORY_SEPARATOR, '', $folder);
        }

        // Loại bỏ các phần path không cần thiết như 'clients/assets/img' hoặc 'img'
        // Chỉ giữ lại phần folder name (ví dụ: 'posts')
        $folderParts = explode(DIRECTORY_SEPARATOR, $folder);
        $validParts = [];
        foreach ($folderParts as $part) {
            $part = trim($part);
            // Bỏ qua các phần như 'clients', 'assets', 'img', 'admins' vì đã có trong rootPath
            if (! empty($part) && ! in_array(strtolower($part), ['clients', 'assets', 'img', 'admins'])) {
                $validParts[] = $part;
            }
        }
        $folder = implode(DIRECTORY_SEPARATOR, $validParts);

        // Build targetPath với DIRECTORY_SEPARATOR nhất quán
        $targetPath = empty($folder) ? $rootPath : $rootPath.DIRECTORY_SEPARATOR.$folder;
        // Đảm bảo targetPath được normalize hoàn toàn
        $targetPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $targetPath);

        Log::debug('Media uploadFiles', [
            'input_folder' => $originalFolder,
            'normalized_folder' => $folder,
            'rootPath' => $rootPath,
            'targetPath' => $targetPath,
            'scope' => $scope,
            'exists' => File::exists($targetPath),
        ]);

        // Ensure folder exists
        if (! File::exists($targetPath)) {
            File::makeDirectory($targetPath, 0755, true);
        }

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            try {
                $result = $this->uploadSingleFile($file, $targetPath, $scope);
                $results[] = $result;
            } catch (\Throwable $e) {
                Log::error('Media upload failed', [
                    'file' => $file->getClientOriginalName(),
                    'folder' => $folder,
                    'error' => $e->getMessage(),
                ]);
                $results[] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'filename' => $file->getClientOriginalName(),
                ];
            }
        }

        return $results;
    }

    /**
     * Upload single file.
     *
     * Giữ nguyên định dạng gốc (không tự convert sang WebP, không tạo thumbnail).
     */
    protected function uploadSingleFile(UploadedFile $file, string $targetPath, string $scope): array
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = strtolower($file->getClientOriginalExtension());
        $slugName = Str::slug($originalName);
        $finalExtension = $extension;
        $finalName = $slugName.'.'.$extension;
        $finalPath = $targetPath.DIRECTORY_SEPARATOR.$finalName;

        // Nếu trùng tên, replace: xóa file cũ
        if (File::exists($finalPath)) {
            File::delete($finalPath);
        }

        // Lưu trực tiếp file gốc (không tạo thumbnail)
        $file->move($targetPath, $finalName);

        if (! File::exists($finalPath)) {
            throw new \Exception('Failed to move uploaded file');
        }

        // Verify file exists before getting info
        if (! File::exists($finalPath)) {
            throw new \Exception('Uploaded file not found at: '.$finalPath);
        }

        $fileInfo = [
            'success' => true,
            'filename' => $finalName,
            'original_name' => $file->getClientOriginalName(),
            'path' => $this->getRelativePath($finalPath, $scope),
            'size' => File::size($finalPath),
            'mime_type' => File::mimeType($finalPath),
            'created_at' => now()->toIso8601String(),
        ];

        // Lưu thông tin vào bảng images để tái sử dụng trong sản phẩm / bài viết
        // Title và alt lưu tên file bỏ đuôi đi
        try {
            $imageTitleWithoutExtension = pathinfo($finalName, PATHINFO_FILENAME);

            Image::create([
                'url' => $finalName,
                'title' => $imageTitleWithoutExtension,
                'notes' => null,
                'alt' => $imageTitleWithoutExtension,
                'is_primary' => false,
                'order' => 0,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Media uploadSingleFile: failed to create Image record', [
                'filename' => $finalName,
                'error' => $e->getMessage(),
            ]);
        }

        $this->logActivity('upload', $fileInfo['path'], $scope);

        return $fileInfo;
    }

    /**
     * Generate unique filename to prevent overwrite
     */
    protected function generateUniqueFilename(string $directory, string $baseName, string $extension): string
    {
        $filename = $baseName.'.'.$extension;
        $counter = 1;

        while (File::exists($directory.'/'.$filename)) {
            $filename = $baseName.'_'.$counter.'.'.$extension;
            $counter++;
        }

        return $filename;
    }

    /**
     * Get relative path from absolute path
     * Returns path relative to root (e.g., "accounts/file.webp" not "admins/img/accounts/file.webp")
     */
    protected function getRelativePath(string $absolutePath, string $scope): string
    {
        $root = $this->getRootPath($scope);

        $rootReal = realpath($root) ?: $root;
        $absoluteReal = realpath($absolutePath) ?: $absolutePath;

        $rootNormalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rootReal);
        $absoluteNormalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $absoluteReal);

        if (str_starts_with($absoluteNormalized, $rootNormalized)) {
            $relative = substr($absoluteNormalized, strlen($rootNormalized));
        } else {
            $marker = match ($scope) {
                'admin' => 'admins'.DIRECTORY_SEPARATOR.'img',
                'client' => 'clients'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'img',
                'catalog' => 'clients'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'catalog',
                default => 'admins'.DIRECTORY_SEPARATOR.'img',
            };
            $pos = stripos($absoluteNormalized, $marker);
            if ($pos !== false) {
                $relative = substr($absoluteNormalized, $pos + strlen($marker));
            } else {
                $relative = basename($absoluteNormalized);
            }
        }

        $relative = trim($relative, DIRECTORY_SEPARATOR);

        return str_replace('\\', '/', $relative);
    }

    /**
     * Rename file
     */
    public function renameFile(string $oldPath, string $newName, string $scope = 'admin'): array
    {
        $rootPath = $this->getRootPath($scope);

        // Normalize path: remove leading/trailing slashes and normalize separators
        $oldPath = trim($oldPath, '/\\');
        $oldPath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $oldPath);

        // Remove any full path prefixes if accidentally included
        $rootNormalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rootPath);
        if (str_starts_with($oldPath, $rootNormalized)) {
            $oldPath = str_replace($rootNormalized.DIRECTORY_SEPARATOR, '', $oldPath);
        }

        $fullOldPath = empty($oldPath) ? $rootPath : $rootPath.DIRECTORY_SEPARATOR.$oldPath;

        Log::debug('Media renameFile', [
            'oldPath' => $oldPath,
            'fullOldPath' => $fullOldPath,
            'newName' => $newName,
            'scope' => $scope,
            'exists' => File::exists($fullOldPath),
        ]);

        if (! File::exists($fullOldPath)) {
            throw new \Exception('File not found');
        }

        // Check if it's a directory
        if (File::isDirectory($fullOldPath)) {
            throw new \Exception('Cannot rename directory. Use renameFolder instead.');
        }

        $directory = dirname($fullOldPath);
        $extension = File::extension($fullOldPath);

        // If newName already has extension, use it; otherwise add extension from original file
        $finalNewName = trim($newName);
        if (! pathinfo($finalNewName, PATHINFO_EXTENSION) && $extension) {
            $finalNewName = $finalNewName.'.'.$extension;
        }

        // Generate unique filename if needed
        $newFilename = $this->generateUniqueFilename($directory, pathinfo($finalNewName, PATHINFO_FILENAME), File::extension($finalNewName));
        $fullNewPath = $directory.DIRECTORY_SEPARATOR.$newFilename;

        try {
            File::move($fullOldPath, $fullNewPath);

            // Rename thumbnail if exists
            $thumbPath = $directory.DIRECTORY_SEPARATOR.'thumbs'.DIRECTORY_SEPARATOR.basename($fullOldPath);
            if (File::exists($thumbPath)) {
                $thumbDir = $directory.DIRECTORY_SEPARATOR.'thumbs';
                if (! File::exists($thumbDir)) {
                    File::makeDirectory($thumbDir, 0755, true);
                }
                $newThumbPath = $thumbDir.DIRECTORY_SEPARATOR.$newFilename;
                File::move($thumbPath, $newThumbPath);
            }

            $relativePath = $this->getRelativePath($fullNewPath, $scope);
            $this->logActivity('rename', $relativePath, $scope, ['old_path' => $oldPath]);

            return [
                'success' => true,
                'filename' => $newFilename,
                'path' => $relativePath,
            ];
        } catch (\Throwable $e) {
            Log::error('Media rename failed', [
                'oldPath' => $oldPath,
                'fullOldPath' => $fullOldPath,
                'newName' => $newName,
                'scope' => $scope,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Move file
     */
    public function moveFile(string $filePath, string $targetFolder, string $scope = 'admin'): array
    {
        $rootPath = $this->getRootPath($scope);
        $fullFilePath = $rootPath.'/'.$filePath;
        $targetPath = $rootPath.'/'.$targetFolder;

        if (! File::exists($fullFilePath)) {
            throw new \Exception('File not found');
        }

        if (! File::exists($targetPath)) {
            File::makeDirectory($targetPath, 0755, true);
        }

        $filename = basename($fullFilePath);
        $newPath = $targetPath.'/'.$filename;
        $newPath = $this->generateUniqueFilename($targetPath, pathinfo($filename, PATHINFO_FILENAME), File::extension($filename));

        File::move($fullFilePath, $targetPath.'/'.$newPath);

        // Move thumbnail
        $thumbPath = dirname($fullFilePath).'/thumbs/'.$filename;
        if (File::exists($thumbPath)) {
            $thumbTarget = $targetPath.'/thumbs';
            if (! File::exists($thumbTarget)) {
                File::makeDirectory($thumbTarget, 0755, true);
            }
            File::move($thumbPath, $thumbTarget.'/'.$newPath);
        }

        $relativePath = $this->getRelativePath($targetPath.'/'.$newPath, $scope);
        $this->logActivity('move', $relativePath, $scope, ['old_path' => $filePath]);

        return [
            'success' => true,
            'path' => $relativePath,
            'filename' => $newPath,
        ];
    }

    /**
     * Copy file
     */
    public function copyFile(string $filePath, string $targetFolder, string $scope = 'admin'): array
    {
        $rootPath = $this->getRootPath($scope);
        $fullFilePath = $rootPath.'/'.$filePath;
        $targetPath = $rootPath.'/'.$targetFolder;

        if (! File::exists($fullFilePath)) {
            throw new \Exception('File not found');
        }

        if (! File::exists($targetPath)) {
            File::makeDirectory($targetPath, 0755, true);
        }

        $filename = basename($fullFilePath);
        $baseName = pathinfo($filename, PATHINFO_FILENAME);
        $extension = File::extension($filename);
        $newFilename = $this->generateUniqueFilename($targetPath, $baseName.'_copy', $extension);

        File::copy($fullFilePath, $targetPath.'/'.$newFilename);

        // Copy thumbnail if exists
        $thumbPath = dirname($fullFilePath).'/thumbs/'.$filename;
        if (File::exists($thumbPath)) {
            $thumbTarget = $targetPath.'/thumbs';
            if (! File::exists($thumbTarget)) {
                File::makeDirectory($thumbTarget, 0755, true);
            }
            File::copy($thumbPath, $thumbTarget.'/'.$newFilename);
        }

        $relativePath = $this->getRelativePath($targetPath.'/'.$newFilename, $scope);
        $this->logActivity('copy', $relativePath, $scope, ['source_path' => $filePath]);

        return [
            'success' => true,
            'path' => $relativePath,
            'filename' => $newFilename,
        ];
    }

    /**
     * Delete file without updating catalog (for bulk delete optimization)
     */
    public function deleteFileWithoutCatalogUpdate(string $filePath, string $scope = 'admin'): bool
    {
        $rootPath = $this->getRootPath($scope);

        // Normalize path: remove leading/trailing slashes and normalize separators
        $filePath = trim($filePath, '/\\');
        $filePath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $filePath);

        // Remove any full path prefixes if accidentally included
        $rootNormalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rootPath);
        if (str_starts_with($filePath, $rootNormalized)) {
            $filePath = str_replace($rootNormalized.DIRECTORY_SEPARATOR, '', $filePath);
        }

        // Nếu path vẫn còn chứa prefix public (clients/assets/img hoặc admins/img hoặc clients/assets/catalog) thì cắt bỏ
        $marker = match ($scope) {
            'admin' => 'admins'.DIRECTORY_SEPARATOR.'img',
            'client' => 'clients'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'img',
            'catalog' => 'clients'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'catalog',
            default => 'admins'.DIRECTORY_SEPARATOR.'img',
        };

        $markerPos = stripos($filePath, $marker);
        if ($markerPos !== false) {
            $filePath = substr($filePath, $markerPos + strlen($marker));
            $filePath = ltrim($filePath, DIRECTORY_SEPARATOR);
        }

        $fullFilePath = empty($filePath) ? $rootPath : $rootPath.DIRECTORY_SEPARATOR.$filePath;

        if (! File::exists($fullFilePath)) {
            return false;
        }

        // Check if it's a directory
        if (File::isDirectory($fullFilePath)) {
            throw new \Exception('Cannot delete directory. Use deleteFolder instead.');
        }

        try {
            // Xóa file trong filesystem
            File::delete($fullFilePath);

            // Xóa thumbnail nếu có
            $thumbPath = dirname($fullFilePath).DIRECTORY_SEPARATOR.'thumbs'.DIRECTORY_SEPARATOR.basename($fullFilePath);
            if (File::exists($thumbPath)) {
                File::delete($thumbPath);
            }

            // Xóa record trong database (chỉ cho non-catalog files)
            if ($scope !== 'catalog') {
                $relativePath = $this->getRelativePath($fullFilePath, $scope);
                $filename = basename($fullFilePath);
                $isResizeFile = $this->isResizeFile($filePath, $scope);

                if (! $isResizeFile) {
                    \App\Models\Image::where(function ($query) use ($filename, $relativePath) {
                        $query->where('url', $filename)
                            ->orWhere('url', $relativePath)
                            ->orWhere('url', 'like', '%/'.$filename)
                            ->orWhere('url', 'like', $relativePath.'%');
                    })->forceDelete();
                }
            }

            $this->logActivity('delete', $filePath, $scope);

            return true;
        } catch (\Throwable $e) {
            Log::error('Media delete failed', [
                'path' => $filePath,
                'fullPath' => $fullFilePath,
                'scope' => $scope,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Delete file
     */
    public function deleteFile(string $filePath, string $scope = 'admin'): bool
    {
        $rootPath = $this->getRootPath($scope);

        // Normalize path: remove leading/trailing slashes and normalize separators
        $filePath = trim($filePath, '/\\');
        $filePath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $filePath);

        // Remove any full path prefixes if accidentally included
        $rootNormalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rootPath);
        if (str_starts_with($filePath, $rootNormalized)) {
            $filePath = str_replace($rootNormalized.DIRECTORY_SEPARATOR, '', $filePath);
        }

        // Nếu path vẫn còn chứa prefix public (clients/assets/img hoặc admins/img) thì cắt bỏ
        $marker = $scope === 'admin'
            ? 'admins'.DIRECTORY_SEPARATOR.'img'
            : 'clients'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'img';

        $markerPos = stripos($filePath, $marker);
        if ($markerPos !== false) {
            $filePath = substr($filePath, $markerPos + strlen($marker));
            $filePath = ltrim($filePath, DIRECTORY_SEPARATOR);
        }

        $fullFilePath = empty($filePath) ? $rootPath : $rootPath.DIRECTORY_SEPARATOR.$filePath;

        Log::debug('Media deleteFile', [
            'filePath' => $filePath,
            'fullFilePath' => $fullFilePath,
            'scope' => $scope,
            'exists' => File::exists($fullFilePath),
        ]);

        if (! File::exists($fullFilePath)) {
            Log::warning('Media delete: File not found', [
                'path' => $filePath,
                'fullPath' => $fullFilePath,
                'scope' => $scope,
            ]);

            return false;
        }

        // Check if it's a directory
        if (File::isDirectory($fullFilePath)) {
            throw new \Exception('Cannot delete directory. Use deleteFolder instead.');
        }

        try {
            // Lấy filename để xóa trong database
            $filename = basename($fullFilePath);

            // Kiểm tra xem file có nằm trong /resize không
            $isResizeFile = $this->isResizeFile($filePath, $scope);

            // Xóa file trong filesystem
            File::delete($fullFilePath);

            // Xóa thumbnail nếu có
            $thumbPath = dirname($fullFilePath).DIRECTORY_SEPARATOR.'thumbs'.DIRECTORY_SEPARATOR.basename($fullFilePath);
            if (File::exists($thumbPath)) {
                File::delete($thumbPath);
            }

            // Xóa record trong database:
            // - Nếu file KHÔNG nằm trong /resize: xóa database (file gốc)
            // - Nếu file nằm trong /resize: cũng xóa database (theo yêu cầu: xóa ảnh trong /resize thì xóa cả database)
            // Lưu ý: Chỉ khi xóa FOLDER trong /resize thì mới KHÔNG xóa database
            $relativePath = $this->getRelativePath($fullFilePath, $scope);
            $filename = basename($fullFilePath);

            // Xóa record nếu url trùng với filename hoặc relative path
            \App\Models\Image::where(function ($query) use ($filename, $relativePath) {
                $query->where('url', $filename)
                    ->orWhere('url', $relativePath)
                    ->orWhere('url', 'like', '%/'.$filename)
                    ->orWhere('url', 'like', $relativePath.'%');
            })->forceDelete();

            // Nếu là file catalog, xóa khỏi link_catalog của products
            if ($scope === 'catalog') {
                $this->removeCatalogFromProducts($relativePath, $filename);
            }

            $this->logActivity('delete', $filePath, $scope);

            return true;
        } catch (\Throwable $e) {
            Log::error('Media delete failed', [
                'path' => $filePath,
                'fullPath' => $fullFilePath,
                'scope' => $scope,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Get file info
     */
    public function getFileInfo(string $filePath, string $scope = 'admin'): ?array
    {
        $rootPath = $this->getRootPath($scope);
        $fullPath = $rootPath.'/'.$filePath;

        if (! File::exists($fullPath)) {
            return null;
        }

        $stat = stat($fullPath);

        return [
            'filename' => basename($fullPath),
            'path' => $this->getRelativePath($fullPath, $scope),
            'size' => File::size($fullPath),
            'mime_type' => File::mimeType($fullPath),
            'extension' => File::extension($fullPath),
            'created_at' => date('Y-m-d H:i:s', $stat['ctime']),
            'modified_at' => date('Y-m-d H:i:s', $stat['mtime']),
            'dimensions' => $this->getImageDimensions($fullPath),
        ];
    }

    /**
     * Get image dimensions if file is image
     */
    protected function getImageDimensions(string $filePath): ?array
    {
        if (! in_array(strtolower(File::extension($filePath)), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            return null;
        }

        $imageInfo = @getimagesize($filePath);
        if ($imageInfo) {
            return [
                'width' => $imageInfo[0],
                'height' => $imageInfo[1],
                'orientation' => $this->getOrientation($imageInfo[0], $imageInfo[1]),
            ];
        }

        return null;
    }

    /**
     * Get image orientation
     */
    protected function getOrientation(int $width, int $height): string
    {
        if ($width === $height) {
            return 'square';
        }

        return $width > $height ? 'landscape' : 'portrait';
    }

    /**
     * Check if file path is inside /resize directory
     * Ví dụ: "clothes/resize/150x150/image.jpg" -> true
     */
    protected function isResizeFile(string $filePath, string $scope): bool
    {
        // Normalize path
        $filePath = str_replace('\\', '/', $filePath);
        $filePath = trim($filePath, '/');
        
        // Kiểm tra xem path có chứa "/resize/" không
        if (str_contains($filePath, '/resize/')) {
            return true;
        }
        
        return false;
    }

    /**
     * Xóa catalog file khỏi link_catalog của products
     * Tối ưu: Sử dụng JSON_CONTAINS và JSON_SEARCH để tìm products có chứa file đó trước
     */
    protected function removeCatalogFromProducts(string $relativePath, string $filename): void
    {
        try {
            // Tạo các search patterns để tìm trong JSON
            $searchPatterns = [
                $relativePath,
                'clients/assets/catalog/'.$relativePath,
                '/clients/assets/catalog/'.$relativePath,
                $filename,
                'clients/assets/catalog/'.$filename,
                '/clients/assets/catalog/'.$filename,
            ];

            // Tối ưu: Tìm products có chứa filename hoặc relativePath trong JSON
            // Sử dụng LIKE trên JSON string để tìm nhanh hơn
            $productIds = [];
            $baseFilename = basename($relativePath) ?: $filename;
            
            // Tìm products có chứa filename trong link_catalog (nhanh nhất)
            $foundIds = \App\Models\Product::whereNotNull('link_catalog')
                ->where('link_catalog', '!=', '[]')
                ->where('link_catalog', '!=', '')
                ->where(function ($query) use ($baseFilename, $relativePath, $filename) {
                    // Tìm bằng filename (phổ biến nhất)
                    $query->whereRaw('link_catalog LIKE ?', ["%{$baseFilename}%"])
                        ->orWhereRaw('link_catalog LIKE ?', ["%{$filename}%"])
                        ->orWhereRaw('link_catalog LIKE ?', ["%{$relativePath}%"]);
                })
                ->pluck('id')
                ->toArray();
            
            $productIds = array_unique($foundIds);
            
            if (empty($productIds)) {
                return; // Không có product nào chứa file này
            }

            // Chỉ update những products thực sự có chứa file
            \App\Models\Product::whereIn('id', $productIds)
                ->chunkById(100, function ($products) use ($searchPatterns) {
                    $productsToUpdate = [];
                    $productIdsToClearCache = [];

                    foreach ($products as $product) {
                        $linkCatalog = $product->link_catalog;
                        
                        if (empty($linkCatalog) || !is_array($linkCatalog)) {
                            continue;
                        }

                        $newLinkCatalog = [];
                        $updated = false;

                        foreach ($linkCatalog as $link) {
                            $link = trim($link);
                            if (empty($link)) {
                                continue;
                            }

                            // Kiểm tra xem link có chứa file đang xóa không
                            $shouldRemove = false;
                            foreach ($searchPatterns as $pattern) {
                                if (str_contains($link, $pattern) || 
                                    basename($link) === basename($pattern) ||
                                    $link === $pattern) {
                                    $shouldRemove = true;
                                    break;
                                }
                            }

                            if (!$shouldRemove) {
                                $newLinkCatalog[] = $link;
                            } else {
                                $updated = true;
                            }
                        }

                        // Collect products cần update
                        if ($updated) {
                            $productsToUpdate[$product->id] = !empty($newLinkCatalog) ? $newLinkCatalog : null;
                            $productIdsToClearCache[] = $product->id;
                        }
                    }

                    // Batch update thay vì save từng product
                    if (!empty($productsToUpdate)) {
                        foreach ($productsToUpdate as $productId => $newLinkCatalog) {
                            \App\Models\Product::where('id', $productId)
                                ->update(['link_catalog' => $newLinkCatalog]);
                        }
                        
                        // Xóa cache sau khi update xong (batch)
                        foreach ($productIdsToClearCache as $productId) {
                            $product = \App\Models\Product::find($productId);
                            if ($product) {
                                $this->clearProductCache($product);
                            }
                        }
                    }
                });
        } catch (\Throwable $e) {
            Log::error('Error removing catalog from products', [
                'relative_path' => $relativePath,
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Xóa nhiều catalog files khỏi link_catalog của products (tối ưu cho bulk delete)
     * Tối ưu: Chỉ query database 1 lần cho tất cả files
     */
    public function removeMultipleCatalogsFromProducts(array $deletedFiles): void
    {
        if (empty($deletedFiles)) {
            return;
        }

        try {
            // Collect tất cả patterns từ tất cả files đã xóa
            $allPatterns = [];
            $allFilenames = [];
            $allRelativePaths = [];

            foreach ($deletedFiles as $file) {
                $relativePath = $file['relativePath'] ?? '';
                $filename = $file['filename'] ?? '';

                if (empty($relativePath) && empty($filename)) {
                    continue;
                }

                $allFilenames[] = $filename;
                $allRelativePaths[] = $relativePath;
                $allFilenames[] = basename($relativePath) ?: $filename;
            }

            // Loại bỏ duplicate và empty
            $allFilenames = array_filter(array_unique($allFilenames));
            $allRelativePaths = array_filter(array_unique($allRelativePaths));

            if (empty($allFilenames) && empty($allRelativePaths)) {
                return;
            }

            // Tìm tất cả products có chứa BẤT KỲ file nào trong danh sách đã xóa (1 query duy nhất)
            $productIds = \App\Models\Product::whereNotNull('link_catalog')
                ->where('link_catalog', '!=', '[]')
                ->where('link_catalog', '!=', '')
                ->where(function ($query) use ($allFilenames, $allRelativePaths) {
                    foreach ($allFilenames as $filename) {
                        $query->orWhereRaw('link_catalog LIKE ?', ["%{$filename}%"]);
                    }
                    foreach ($allRelativePaths as $relativePath) {
                        if (!empty($relativePath)) {
                            $query->orWhereRaw('link_catalog LIKE ?', ["%{$relativePath}%"]);
                        }
                    }
                })
                ->pluck('id')
                ->toArray();

            $productIds = array_unique($productIds);

            if (empty($productIds)) {
                return; // Không có product nào chứa các files này
            }

            // Tạo tất cả search patterns từ tất cả files
            $allSearchPatterns = [];
            foreach ($deletedFiles as $file) {
                $relativePath = $file['relativePath'] ?? '';
                $filename = $file['filename'] ?? '';
                
                if (empty($relativePath) && empty($filename)) {
                    continue;
                }

                $allSearchPatterns = array_merge($allSearchPatterns, [
                    $relativePath,
                    'clients/assets/catalog/'.$relativePath,
                    '/clients/assets/catalog/'.$relativePath,
                    $filename,
                    'clients/assets/catalog/'.$filename,
                    '/clients/assets/catalog/'.$filename,
                ]);
            }
            $allSearchPatterns = array_unique(array_filter($allSearchPatterns));

            // Chỉ update những products thực sự có chứa files
            \App\Models\Product::whereIn('id', $productIds)
                ->chunkById(100, function ($products) use ($allSearchPatterns) {
                    $productsToUpdate = [];
                    $productIdsToClearCache = [];

                    foreach ($products as $product) {
                        $linkCatalog = $product->link_catalog;
                        
                        if (empty($linkCatalog) || !is_array($linkCatalog)) {
                            continue;
                        }

                        $newLinkCatalog = [];
                        $updated = false;

                        foreach ($linkCatalog as $link) {
                            $link = trim($link);
                            if (empty($link)) {
                                continue;
                            }

                            // Kiểm tra xem link có chứa BẤT KỲ file nào đã xóa không
                            $shouldRemove = false;
                            foreach ($allSearchPatterns as $pattern) {
                                if (str_contains($link, $pattern) || 
                                    basename($link) === basename($pattern) ||
                                    $link === $pattern) {
                                    $shouldRemove = true;
                                    break;
                                }
                            }

                            if (!$shouldRemove) {
                                $newLinkCatalog[] = $link;
                            } else {
                                $updated = true;
                            }
                        }

                        // Collect products cần update
                        if ($updated) {
                            $productsToUpdate[$product->id] = !empty($newLinkCatalog) ? $newLinkCatalog : null;
                            $productIdsToClearCache[] = $product->id;
                        }
                    }

                    // Batch update thay vì save từng product
                    if (!empty($productsToUpdate)) {
                        foreach ($productsToUpdate as $productId => $newLinkCatalog) {
                            \App\Models\Product::where('id', $productId)
                                ->update(['link_catalog' => $newLinkCatalog]);
                        }
                        
                        // Xóa cache sau khi update xong (batch)
                        foreach ($productIdsToClearCache as $productId) {
                            $product = \App\Models\Product::find($productId);
                            if ($product) {
                                $this->clearProductCache($product);
                            }
                        }
                    }
                });
        } catch (\Throwable $e) {
            Log::error('Error removing multiple catalogs from products', [
                'deleted_files_count' => count($deletedFiles),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Xóa cache của sản phẩm
     */
    protected function clearProductCache(\App\Models\Product $product): void
    {
        try {
            $slug = $product->slug;
            $productId = $product->id;

            // Xóa các cache chính của product
            Cache::forget('product_detail_'.$slug);
            Cache::forget('slug_type_'.$slug);
            Cache::forget('related_products_'.$productId);
            Cache::forget('vouchers_for_product_'.$productId);
            
            // Xóa cache comments nếu có (pattern: product_comments_{id}_{timestamp})
            // Không thể xóa chính xác vì có timestamp, nhưng sẽ tự expire sau 6h
            
            // Xóa cache featured products sidebar nếu product này là featured
            Cache::forget('featured_products_sidebar');
            Cache::forget('products_featured_home');
        } catch (\Throwable $e) {
            Log::warning('Error clearing product cache', [
                'product_id' => $product->id ?? null,
                'product_slug' => $product->slug ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log media activity
     */
    protected function logActivity(string $action, string $path, string $scope, array $metadata = []): void
    {
        $logPath = storage_path('logs/media.log');
        $logDir = dirname($logPath);

        if (! File::exists($logDir)) {
            File::makeDirectory($logDir, 0755, true);
        }

        $logEntry = [
            'timestamp' => now()->toIso8601String(),
            'action' => $action,
            'path' => $path,
            'scope' => $scope,
            'user_id' => Auth::id(),
            'user_email' => Auth::user()?->email,
            'metadata' => $metadata,
        ];

        File::append($logPath, json_encode($logEntry).PHP_EOL);
    }
}
