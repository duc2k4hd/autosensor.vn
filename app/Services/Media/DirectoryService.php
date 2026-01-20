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

    protected string $catalogRoot;

    public function __construct()
    {
        $this->adminRoot = public_path('admins/img');
        $this->clientRoot = public_path('clients/assets/img');
        $this->catalogRoot = public_path('clients/assets/catalog');
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

        // Prevent deleting root folders (protected folders)
        if ($this->isRootFolder($path, $scope)) {
            $pathParts = explode('/', $path);
            $folderName = $pathParts[0] ?? $path;
            throw new \Exception("Không được phép xóa folder gốc '{$folderName}'. Folder này được bảo vệ và không thể xóa.");
        }

        // Check if folder is empty
        $files = File::files($fullPath);
        $directories = File::directories($fullPath);

        if (! $force && (count($files) > 0 || count($directories) > 0)) {
            throw new \Exception('Folder is not empty. Use force delete to remove all contents.');
        }

        // Kiểm tra xem folder có nằm trong /resize không
        $isResizeFolder = $this->isResizeFolder($path, $scope);

        // Nếu KHÔNG phải folder trong /resize, mới xóa record trong database
        if (! $isResizeFolder) {
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

                // Nếu là file catalog, xóa khỏi link_catalog của products
                if ($scope === 'catalog') {
                    $this->removeCatalogFromProducts($relativePath, $filename);
                }
            }
        }
        // Nếu là folder trong /resize, chỉ xóa file, KHÔNG xóa database

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
     * Check if path is root folder (protected folders that cannot be deleted)
     */
    protected function isRootFolder(string $path, string $scope): bool
    {
        // Danh sách các folder gốc được bảo vệ, không được phép xóa
        $rootFolders = match ($scope) {
            'admin' => [
                'accounts', 'avatars', 'banners', 'brands', 'general', 'icons', 'popup',
            ],
            'client' => [
                'accounts', 'avatars', 'banners', 'brands', 'business', 'categories',
                'clothes', 'frame', 'icon', 'imports', 'other', 'popup', 'posts', 'vouchers',
            ],
            'catalog' => [
                // Catalog không có folder gốc được bảo vệ, có thể xóa tất cả
            ],
            default => [],
        };

        $pathParts = explode('/', $path);
        $firstPart = $pathParts[0] ?? '';

        // Kiểm tra xem folder đầu tiên có nằm trong danh sách bảo vệ không
        // Nếu path chỉ có 1 phần (folder gốc) và nằm trong danh sách -> không được xóa
        return in_array($firstPart, $rootFolders) && count($pathParts) === 1;
    }

    /**
     * Check if path is a folder inside /resize directory
     * Ví dụ: "clothes/resize/150x150" -> true
     */
    protected function isResizeFolder(string $path, string $scope): bool
    {
        // Normalize path
        $path = str_replace('\\', '/', $path);
        $path = trim($path, '/');
        
        // Kiểm tra xem path có chứa "/resize/" hoặc kết thúc bằng "/resize" không
        // Và phải là folder (không phải file)
        if (str_contains($path, '/resize/') || preg_match('#/resize$#', $path)) {
            return true;
        }
        
        // Kiểm tra trường hợp đặc biệt: path = "clothes/resize/150x150"
        $pathParts = explode('/', $path);
        $resizeIndex = array_search('resize', $pathParts);
        
        // Nếu tìm thấy "resize" và có phần sau nó (ví dụ: "150x150")
        if ($resizeIndex !== false && isset($pathParts[$resizeIndex + 1])) {
            return true;
        }
        
        return false;
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
            // Fallback: cố gắng tìm phần sau marker path
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
                // Fallback cuối cùng: chỉ trả về tên file
                $relative = basename($absoluteNormalized);
            }
        }

        $relative = trim($relative, DIRECTORY_SEPARATOR);

        return str_replace('\\', '/', $relative);
    }

    /**
     * Xóa catalog file khỏi link_catalog của products
     * Tối ưu: Sử dụng LIKE trên JSON string để tìm products có chứa file đó trước
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
     * Xóa cache của sản phẩm
     */
    protected function clearProductCache(\App\Models\Product $product): void
    {
        try {
            $slug = $product->slug;
            $productId = $product->id;

            // Xóa các cache chính của product
            \Illuminate\Support\Facades\Cache::forget('product_detail_'.$slug);
            \Illuminate\Support\Facades\Cache::forget('slug_type_'.$slug);
            \Illuminate\Support\Facades\Cache::forget('related_products_'.$productId);
            \Illuminate\Support\Facades\Cache::forget('vouchers_for_product_'.$productId);
            
            // Xóa cache featured products nếu product này là featured
            \Illuminate\Support\Facades\Cache::forget('featured_products_sidebar');
            \Illuminate\Support\Facades\Cache::forget('products_featured_home');
        } catch (\Throwable $e) {
            Log::warning('Error clearing product cache', [
                'product_id' => $product->id ?? null,
                'product_slug' => $product->slug ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
