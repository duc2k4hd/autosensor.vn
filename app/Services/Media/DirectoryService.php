<?php

namespace App\Services\Media;

use App\Models\Image;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DirectoryService
{
    protected string $adminRoot;

    protected string $clientRoot;

    public function __construct()
    {
        $this->adminRoot = public_path('admins/img');
        $this->clientRoot = public_path('clients/assets/img');
    }

    /**
     * Get root path based on scope
     */
    public function getRootPath(string $scope = 'admin'): string
    {
        return $scope === 'admin' ? $this->adminRoot : $this->clientRoot;
    }

    /**
     * Create folder
     */
    public function createFolder(?string $path, string $name, string $scope = 'admin'): array
    {
        // Cho phép null từ request, luôn chuẩn hóa về string
        $path = $path ?? '';
        $rootPath = $this->getRootPath($scope);
        $parentPath = empty($path) ? $rootPath : $rootPath.'/'.$path;
        $slugName = Str::slug($name);
        $folderPath = $parentPath.'/'.$slugName;

        // Check if folder already exists
        if (File::exists($folderPath)) {
            throw new \Exception('Folder already exists');
        }

        // Create folder
        File::makeDirectory($folderPath, 0755, true);

        // Create thumbs directory
        File::makeDirectory($folderPath.'/thumbs', 0755, true);

        $relativePath = $this->getRelativePath($folderPath, $scope);

        return [
            'success' => true,
            'name' => $slugName,
            'path' => $relativePath,
        ];
    }

    /**
     * Rename folder
     */
    public function renameFolder(string $oldPath, string $newName, string $scope = 'admin'): array
    {
        $rootPath = $this->getRootPath($scope);
        $fullOldPath = empty($oldPath) ? $rootPath : $rootPath.'/'.$oldPath;

        if (! File::isDirectory($fullOldPath)) {
            throw new \Exception('Folder not found');
        }

        $parentPath = dirname($fullOldPath);
        $slugName = Str::slug($newName);
        $newPath = $parentPath.'/'.$slugName;

        // Check if new name already exists
        if (File::exists($newPath)) {
            throw new \Exception('Folder name already exists');
        }

        // Move folder
        File::move($fullOldPath, $newPath);

        $relativePath = $this->getRelativePath($newPath, $scope);

        return [
            'success' => true,
            'name' => $slugName,
            'path' => $relativePath,
        ];
    }

    /**
     * Delete folder
     */
    public function deleteFolder(string $path, string $scope = 'admin', bool $force = false): bool
    {
        $rootPath = $this->getRootPath($scope);
        $fullPath = empty($path) ? $rootPath : $rootPath.'/'.$path;

        if (! File::isDirectory($fullPath)) {
            return false;
        }

        // Prevent deleting root folders
        if ($this->isRootFolder($path, $scope)) {
            throw new \Exception('Cannot delete root folder');
        }

        // Check if folder is empty
        $files = File::files($fullPath);
        $directories = File::directories($fullPath);

        if (! $force && (count($files) > 0 || count($directories) > 0)) {
            throw new \Exception('Folder is not empty. Use force delete to remove all contents.');
        }

        // Trước khi xóa folder, cần xóa tất cả record trong bảng images
        // Lấy danh sách tất cả file trong folder (bao gồm cả subfolder nếu force = true)
        $allFiles = $this->getAllFilesInFolder($fullPath, $force);

        // Xóa record trong bảng images cho từng file
        foreach ($allFiles as $filePath) {
            $relativePath = $this->getRelativePath($filePath, $scope);
            $filename = basename($filePath);

            // Xóa record nếu url trùng với filename hoặc relative path
            Image::where(function ($query) use ($filename, $relativePath) {
                $query->where('url', $filename)
                    ->orWhere('url', $relativePath)
                    ->orWhere('url', 'like', '%/'.$filename)
                    ->orWhere('url', 'like', $relativePath.'%');
            })->forceDelete();
        }

        // Delete folder and all contents
        File::deleteDirectory($fullPath);

        return true;
    }

    /**
     * Get all files in folder recursively
     */
    protected function getAllFilesInFolder(string $folderPath, bool $recursive = false): array
    {
        $files = [];

        if (! File::isDirectory($folderPath)) {
            return $files;
        }

        // Get files in current directory
        $currentFiles = File::files($folderPath);
        foreach ($currentFiles as $file) {
            $files[] = $file->getPathname();
        }

        // If recursive, get files in subdirectories
        if ($recursive) {
            $directories = File::directories($folderPath);
            foreach ($directories as $directory) {
                // Skip thumbs directory
                if (basename($directory) === 'thumbs') {
                    continue;
                }
                $subFiles = $this->getAllFilesInFolder($directory, true);
                $files = array_merge($files, $subFiles);
            }
        }

        return $files;
    }

    /**
     * Check if path is root folder
     */
    protected function isRootFolder(string $path, string $scope): bool
    {
        $rootFolders = $scope === 'admin' ? [
            'accounts', 'banners', 'general', 'icons',
        ] : [
            'accounts', 'banners', 'business', 'categories',
            'clothes', 'frame', 'icon', 'imports', 'other', 'posts', 'vouchers',
        ];

        $pathParts = explode('/', $path);
        $firstPart = $pathParts[0] ?? '';

        return in_array($firstPart, $rootFolders) && count($pathParts) === 1;
    }

    /**
     * Get folder tree structure
     */
    public function getFolderTree(string $scope = 'admin', ?string $basePath = null): array
    {
        $rootPath = $this->getRootPath($scope);
        $basePath = empty($basePath) ? $rootPath : ($rootPath.'/'.$basePath);

        if (! File::isDirectory($basePath)) {
            return [];
        }

        $tree = [];
        $directories = File::directories($basePath);

        foreach ($directories as $directory) {
            $name = basename($directory);
            // Skip thumbs directory
            if ($name === 'thumbs') {
                continue;
            }

            $relativePath = $this->getRelativePath($directory, $scope);
            $subDirectories = File::directories($directory);
            $hasChildren = count($subDirectories) > 0;

            $tree[] = [
                'name' => $name,
                'path' => $relativePath,
                'has_children' => $hasChildren,
                'children' => $hasChildren ? $this->getFolderTree($scope, $relativePath) : [],
            ];
        }

        Log::debug('Media getFolderTree: Found folders', ['count' => count($tree)]);

        return $tree;
    }

    /**
     * List files in directory with pagination support
     * 
     * @param string $path Relative path
     * @param string $scope 'admin' or 'client'
     * @param array $filters Filters (extension, min_size, max_size, orientation)
     * @param int $page Page number (1-based)
     * @param int $perPage Items per page
     * @param string|null $search Search query (searches in filename)
     * @return array ['files' => [], 'total' => int, 'page' => int, 'per_page' => int, 'has_more' => bool]
     */
    public function listFiles(
        string $path = '', 
        string $scope = 'admin', 
        array $filters = [],
        int $page = 1,
        int $perPage = 50,
        ?string $search = null
    ): array {
        $path = $path ?? '';
        $rootPath = $this->getRootPath($scope);
        $fullPath = empty($path) ? $rootPath : $rootPath.'/'.$path;

        Log::debug('Media listFiles', [
            'path' => $path,
            'scope' => $scope,
            'page' => $page,
            'per_page' => $perPage,
            'search' => $search,
        ]);

        if (! File::isDirectory($fullPath)) {
            Log::warning('Media listFiles: Directory not found', ['fullPath' => $fullPath]);

            return [
                'files' => [],
                'total' => 0,
                'page' => $page,
                'per_page' => $perPage,
                'has_more' => false,
            ];
        }

        // ✅ Dùng iterator và STOP SỚM khi đủ page
        $iterator = new \FilesystemIterator($fullPath, \FilesystemIterator::SKIP_DOTS);
        
        $offset = ($page - 1) * $perPage;
        $limit = $perPage;
        $files = [];
        $total = 0; // Đếm files sau filter (ước lượng, không chính xác 100%)
        $searchLower = $search ? strtolower($search) : null;
        
        // ✅ Helper: Suy đoán mime type từ extension (không disk IO)
        $getMimeType = function (string $ext): string {
            return match (strtolower($ext)) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'webp' => 'image/webp',
                'gif' => 'image/gif',
                'svg' => 'image/svg+xml',
                'pdf' => 'application/pdf',
                'zip' => 'application/zip',
                default => 'application/octet-stream',
            };
        };
        
        foreach ($iterator as $fileInfo) {
            if (! $fileInfo->isFile()) {
                continue;
            }

            $filename = $fileInfo->getFilename();
            $extension = strtolower($fileInfo->getExtension());
            
            // ✅ Filter sớm (trước khi xử lý metadata)
            if ($searchLower && strpos(strtolower($filename), $searchLower) === false) {
                continue;
            }

            if (! empty($filters['extension']) && $extension !== $filters['extension']) {
                continue;
            }

            // Đếm total (sau khi filter)
            $total++;

            // ✅ Skip files trước offset
            if ($total <= $offset) {
                continue;
            }

            // ✅ STOP SỚM khi đủ limit
            if (count($files) >= $limit) {
                break;
            }

            // Chỉ xử lý metadata cho files trong page
            $size = $fileInfo->getSize();
            $mtime = $fileInfo->getMTime();
            
            // Apply size filters
            if (! empty($filters['min_size']) && $size < $filters['min_size']) {
                continue;
            }

            if (! empty($filters['max_size']) && $size > $filters['max_size']) {
                continue;
            }

            // ✅ Dùng extension mapping thay vì File::mimeType() (không disk IO)
            $mimeType = $getMimeType($extension);
            
            // Apply orientation filter nếu có (chỉ khi cần)
            if (! empty($filters['orientation']) && str_starts_with($mimeType, 'image/')) {
                $imageInfo = @getimagesize($fileInfo->getPathname());
                if ($imageInfo) {
                    $orientation = $this->getOrientation($imageInfo[0], $imageInfo[1]);
                    if ($orientation !== $filters['orientation']) {
                        continue;
                    }
                }
            }
            
            $relativePath = $this->getRelativePath($fileInfo->getPathname(), $scope);

            $fileData = [
                'filename' => $filename,
                'path' => $relativePath,
                'size' => $size,
                'mime_type' => $mimeType,
                'extension' => $extension,
                'created_at' => date('Y-m-d H:i:s', $fileInfo->getCTime()),
                'modified_at' => date('Y-m-d H:i:s', $mtime),
                '_mtime_sort' => $mtime,
            ];

            // ✅ Chỉ check thumbnail path nếu là image
            if (str_starts_with($mimeType, 'image/')) {
                $thumbPath = $this->getThumbnailPath($fileInfo->getPathname(), $scope);
                if ($thumbPath) {
                    $fileData['thumbnail_path'] = $thumbPath;
                }
            }

            $files[] = $fileData;
        }

        // ✅ CHỈ sort 50 files trong page (không sort toàn bộ)
        usort($files, function ($a, $b) {
            return $b['_mtime_sort'] <=> $a['_mtime_sort']; // DESC: newest first
        });

        // Remove sort helper
        foreach ($files as &$file) {
            unset($file['_mtime_sort']);
        }
        unset($file);

        // Tính has_more (ước lượng dựa trên việc có đủ limit hay không)
        $hasMore = count($files) >= $limit;

        return [
            'files' => $files,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'has_more' => $hasMore,
        ];
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
     * Get thumbnail path for an image file
     */
    protected function getThumbnailPath(string $filePath, string $scope): ?string
    {
        $directory = dirname($filePath);
        $filename = basename($filePath);

        // Thumbnail filename: convert extension to .webp
        $thumbFilename = preg_replace('/\.(jpg|jpeg|png|gif|webp)$/i', '.webp', $filename);
        $thumbPath = $directory.'/thumbs/'.$thumbFilename;

        if (File::exists($thumbPath)) {
            return $this->getRelativePath($thumbPath, $scope);
        }

        // If thumbnail doesn't exist, return original image path
        return $this->getRelativePath($filePath, $scope);
    }

    /**
     * Get relative path from absolute path
     * Returns path relative to root (e.g., "accounts/file.webp" not "admins/img/accounts/file.webp")
     */
    protected function getRelativePath(string $absolutePath, string $scope): string
    {
        $root = $this->getRootPath($scope);

        // Chuẩn hóa path cho Windows/Linux
        $rootReal = realpath($root) ?: $root;
        $absoluteReal = realpath($absolutePath) ?: $absolutePath;

        $rootNormalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rootReal);
        $absoluteNormalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $absoluteReal);

        // Nếu absolute nằm trong root thì cắt phần root đi
        if (str_starts_with($absoluteNormalized, $rootNormalized)) {
            $relative = substr($absoluteNormalized, strlen($rootNormalized));
        } else {
            // Fallback: cố gắng tìm phần sau "admins/img" hoặc "clients/assets/img"
            $marker = $scope === 'admin' ? 'admins'.DIRECTORY_SEPARATOR.'img' : 'clients'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'img';
            $pos = stripos($absoluteNormalized, $marker);
            if ($pos !== false) {
                $relative = substr($absoluteNormalized, $pos + strlen($marker));
            } else {
                // Fallback cuối cùng: chỉ trả về tên file
                $relative = basename($absoluteNormalized);
            }
        }

        $relative = trim($relative, DIRECTORY_SEPARATOR);

        return str_replace('\\', '/', $relative);
    }
}
