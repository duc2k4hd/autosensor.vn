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
     * List files in directory
     */
    public function listFiles(string $path = '', string $scope = 'admin', array $filters = []): array
    {
        $path = $path ?? '';
        $rootPath = $this->getRootPath($scope);
        $fullPath = empty($path) ? $rootPath : $rootPath.'/'.$path;

        Log::debug('Media listFiles', [
            'path' => $path,
            'scope' => $scope,
            'rootPath' => $rootPath,
            'fullPath' => $fullPath,
            'exists' => File::exists($fullPath),
            'isDirectory' => File::isDirectory($fullPath),
        ]);

        if (! File::isDirectory($fullPath)) {
            Log::warning('Media listFiles: Directory not found', ['fullPath' => $fullPath]);

            return [];
        }

        $files = File::files($fullPath);
        Log::debug('Media listFiles: Found files', ['count' => count($files)]);
        $result = [];

        foreach ($files as $file) {
            $filename = $file->getFilename();
            $extension = strtolower($file->getExtension());
            $size = $file->getSize();
            $mimeType = File::mimeType($file->getPathname());
            $stat = stat($file->getPathname());

            // Apply filters
            if (! empty($filters['extension']) && $extension !== $filters['extension']) {
                continue;
            }

            if (! empty($filters['min_size']) && $size < $filters['min_size']) {
                continue;
            }

            if (! empty($filters['max_size']) && $size > $filters['max_size']) {
                continue;
            }

            $relativePath = $this->getRelativePath($file->getPathname(), $scope);

            $fileInfo = [
                'filename' => $filename,
                'path' => $relativePath,
                'size' => $size,
                'mime_type' => $mimeType,
                'extension' => $extension,
                'created_at' => date('Y-m-d H:i:s', $stat['ctime']),
                'modified_at' => date('Y-m-d H:i:s', $stat['mtime']),
            ];

            // Add image dimensions and thumbnail path if image
            if (str_starts_with($mimeType, 'image/')) {
                $imageInfo = @getimagesize($file->getPathname());
                if ($imageInfo) {
                    $fileInfo['dimensions'] = [
                        'width' => $imageInfo[0],
                        'height' => $imageInfo[1],
                        'orientation' => $this->getOrientation($imageInfo[0], $imageInfo[1]),
                    ];
                }

                // Get thumbnail path
                $thumbPath = $this->getThumbnailPath($file->getPathname(), $scope);
                if ($thumbPath) {
                    $fileInfo['thumbnail_path'] = $thumbPath;
                }
            }

            $result[] = $fileInfo;
        }

        return $result;
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
