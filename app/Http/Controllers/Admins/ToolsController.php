<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Product;
use App\Models\Tag;
use App\Models\Image;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class ToolsController extends Controller
{
    /**
     * Kiểm tra an toàn cho server 2GB RAM
     * Giới hạn nghiêm ngặt để tránh OOM
     */
    private function checkServerSafety(): void
    {
        $memoryLimit = ini_get('memory_limit');
        $memoryLimitBytes = $this->parseMemoryLimit($memoryLimit);
        
        // Nếu memory limit < 512MB, cảnh báo
        if ($memoryLimitBytes < 512 * 1024 * 1024) {
            Log::warning('🔴 [ToolsController] Low memory limit detected', [
                'memory_limit' => $memoryLimit,
                'bytes' => $memoryLimitBytes,
            ]);
        }
        
        // Kiểm tra memory usage hiện tại
        $currentMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        
        // Nếu đã dùng > 80% memory limit, cảnh báo
        if ($memoryLimitBytes > 0 && ($currentMemory / $memoryLimitBytes) > 0.8) {
            Log::warning('🔴 [ToolsController] High memory usage detected', [
                'current' => $this->formatBytes($currentMemory),
                'peak' => $this->formatBytes($peakMemory),
                'limit' => $memoryLimit,
                'usage_percent' => round(($currentMemory / $memoryLimitBytes) * 100, 2),
            ]);
        }
    }
    
    /**
     * Parse memory limit string thành bytes
     */
    private function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;
        
        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }

    /**
     * Hiển thị trang tools
     */
    public function index(): View
    {
        return view('admins.tools.index');
    }

    /**
     * Cập nhật usage_count cho tất cả tags dựa trên số products/posts sử dụng
     * Tối ưu cho database lớn: không load tất cả tags vào memory
     */
    private function updateUsageCount(): void
    {
        Log::info('🔵 [DEBUG ToolsController] updateUsageCount - Starting');
        
        // Bước 1: Reset tất cả usage_count về 0 bằng SQL (nhanh, không load vào memory)
        DB::table('tags')->update(['usage_count' => 0]);
        Log::info('🟢 [DEBUG ToolsController] All tags usage_count reset to 0');
        
        // Bước 2: Đếm từ products - sử dụng chunk để tránh load hết vào memory
        $productCount = 0;
        $productTagCount = 0;
        
        Product::whereNotNull('tag_ids')
            ->select('id', 'tag_ids')
            ->orderBy('id')
            ->chunk(1000, function ($products) use (&$productCount, &$productTagCount) {
                $tagUsageCounts = [];
                
                foreach ($products as $product) {
                    $productCount++;
                    
                    if (is_array($product->tag_ids)) {
                        $tagIds = $product->tag_ids;
                    } elseif (is_string($product->tag_ids) && !empty($product->tag_ids)) {
                        $tagIds = json_decode($product->tag_ids, true) ?? [];
                    } else {
                        $tagIds = [];
                    }
                    
                    foreach ($tagIds as $tagId) {
                        if (!isset($tagUsageCounts[$tagId])) {
                            $tagUsageCounts[$tagId] = 0;
                        }
                        $tagUsageCounts[$tagId]++;
                        $productTagCount++;
                    }
                }
                
                // Cập nhật batch cho chunk này - Sử dụng CASE WHEN để tránh N+1 queries
                if (!empty($tagUsageCounts)) {
                    $this->batchUpdateTagUsageCounts($tagUsageCounts);
                }
            });
        
        Log::info('🔵 [DEBUG ToolsController] Products processed', [
            'products_count' => $productCount,
            'product_tag_relations' => $productTagCount,
        ]);
        
        // Bước 3: Đếm từ posts - sử dụng chunk để tránh load hết vào memory
        $postCount = 0;
        $postTagCount = 0;
        
        Post::whereNotNull('tag_ids')
            ->select('id', 'tag_ids')
            ->orderBy('id')
            ->chunk(1000, function ($posts) use (&$postCount, &$postTagCount) {
                $tagUsageCounts = [];
                
                foreach ($posts as $post) {
                    $postCount++;
                    
                    if (is_array($post->tag_ids)) {
                        $tagIds = $post->tag_ids;
                    } elseif (is_string($post->tag_ids) && !empty($post->tag_ids)) {
                        $tagIds = json_decode($post->tag_ids, true) ?? [];
                    } else {
                        $tagIds = [];
                    }
                    
                    foreach ($tagIds as $tagId) {
                        if (!isset($tagUsageCounts[$tagId])) {
                            $tagUsageCounts[$tagId] = 0;
                        }
                        $tagUsageCounts[$tagId]++;
                        $postTagCount++;
                    }
                }
                
                // Cập nhật batch cho chunk này - Sử dụng CASE WHEN để tránh N+1 queries
                if (!empty($tagUsageCounts)) {
                    $this->batchUpdateTagUsageCounts($tagUsageCounts);
                }
            });
        
        Log::info('🟢 [DEBUG ToolsController] Usage count updated', [
            'products_processed' => $productCount,
            'posts_processed' => $postCount,
            'product_tag_relations' => $productTagCount,
            'post_tag_relations' => $postTagCount,
        ]);
    }

    /**
     * Xóa tất cả các tags không được bài viết và sản phẩm sử dụng
     * Tối ưu cho database lớn: xóa trực tiếp bằng SQL, không load vào memory
     */
    public function deleteUnusedTags(Request $request): JsonResponse
    {
        try {
            // Kiểm tra an toàn server
            $this->checkServerSafety();
            
            // Tăng timeout cho transaction lớn
            set_time_limit(300);
            
            DB::beginTransaction();

            Log::info('🔵 [DEBUG ToolsController] deleteUnusedTags - Starting');

            // Cập nhật usage_count trước
            Log::info('🔵 [DEBUG ToolsController] Updating usage count...');
            $this->updateUsageCount();
            Log::info('🟢 [DEBUG ToolsController] Usage count updated');

            // Đếm số tags sẽ bị xóa (chỉ count, không load)
            $deletedCount = Tag::where('usage_count', 0)->count();
            
            Log::info('🔵 [DEBUG ToolsController] Tags to delete count:', ['count' => $deletedCount]);

            // Xóa trực tiếp bằng SQL để tránh load hàng triệu tags vào memory
            // Chỉ lấy một số thông tin để log (giới hạn 1000 tags đầu tiên)
            $sampleTags = Tag::where('usage_count', 0)
                ->select('id', 'name', 'slug', 'entity_type', 'entity_id', 'usage_count')
                ->orderBy('id', 'asc')
                ->limit(1000)
                ->get()
                ->toArray();

            // Xóa tất cả tags có usage_count = 0 bằng SQL (nhanh, không load vào memory)
            $deleted = DB::table('tags')
                ->where('usage_count', 0)
                ->delete();

            DB::commit();

            Log::info('🟢 [DEBUG ToolsController] Tags deleted', [
                'deleted_count' => $deleted,
                'expected_count' => $deletedCount,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Đã xóa thành công {$deleted} tag(s) không được sử dụng.",
                'deleted_count' => $deleted,
                'sample_deleted_tags' => $sampleTags, // Chỉ trả về mẫu để hiển thị
                'has_more' => $deletedCount > 1000,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('🔴 [DEBUG ToolsController] Exception in deleteUnusedTags', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa tags: ' . $e->getMessage(),
                'error' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * Lấy thống kê tags không được sử dụng (không xóa, chỉ để xem)
     * Tối ưu cho database lớn: chỉ query tags có usage_count = 0, giới hạn số lượng hiển thị
     */
    public function getUnusedTagsStats(): JsonResponse
    {
        try {
            Log::info('🔵 [DEBUG ToolsController] getUnusedTagsStats - Starting');
            
            // Cập nhật usage_count trước
            Log::info('🔵 [DEBUG ToolsController] Updating usage count...');
            $this->updateUsageCount();
            Log::info('🟢 [DEBUG ToolsController] Usage count updated');

            // Đếm tổng số tags không được sử dụng (chỉ count, không load)
            $unusedCount = Tag::where('usage_count', 0)->count();
            
            Log::info('🔵 [DEBUG ToolsController] Found unused tags count:', ['count' => $unusedCount]);
            
            // Chỉ load một số lượng giới hạn tags để hiển thị (ví dụ: 1000 tags đầu tiên)
            // Để tránh load hàng triệu tags vào memory
            $limit = 1000;
            $unusedTags = Tag::where('usage_count', 0)
                ->select('id', 'name', 'slug', 'entity_type', 'entity_id', 'usage_count', 'created_at')
                ->orderBy('id', 'asc')
                ->limit($limit)
                ->get();
            
            $unusedTagsData = [];
            foreach ($unusedTags as $tag) {
                $entityTypeLabel = $tag->entity_type === Product::class ? 'Sản phẩm' : 
                                  ($tag->entity_type === Post::class ? 'Bài viết' : $tag->entity_type);
                
                $unusedTagsData[] = [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                    'entity_type' => $tag->entity_type,
                    'entity_type_label' => $entityTypeLabel,
                    'entity_id' => $tag->entity_id,
                    'usage_count' => $tag->usage_count,
                    'created_at' => $tag->created_at->format('Y-m-d H:i:s'),
                ];
            }

            Log::info('🔵 [DEBUG ToolsController] Preparing response', [
                'unused_count' => $unusedCount,
                'tags_loaded' => count($unusedTagsData),
                'limit' => $limit,
            ]);

            $response = [
                'success' => true,
                'unused_count' => $unusedCount,
                'unused_tags' => $unusedTagsData,
                'limit' => $limit,
                'has_more' => $unusedCount > $limit,
            ];

            Log::info('🟢 [DEBUG ToolsController] Returning response', [
                'success' => true,
                'unused_count' => $unusedCount,
                'tags_returned' => count($unusedTagsData),
            ]);

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('🔴 [DEBUG ToolsController] Exception in getUnusedTagsStats', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
                'error' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    /**
     * Lấy thống kê ảnh sản phẩm không được sử dụng
     * Tối ưu: Sử dụng iterator, cache queries, xử lý theo batch
     */
    public function getUnusedImagesStats(): JsonResponse
    {
        try {
            // Kiểm tra an toàn server
            $this->checkServerSafety();
            
            // Tăng timeout và memory limit
            set_time_limit(300); // 5 phút
            ini_set('memory_limit', '512M');
            
            Log::info('🔵 [DEBUG ToolsController] getUnusedImagesStats - Starting');
            
            $clothesPath = public_path('clients/assets/img/clothes');
            
            if (!File::exists($clothesPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thư mục không tồn tại: ' . $clothesPath,
                ], 404);
            }
            
            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
            $unusedImages = [];
            $totalSize = 0;
            $checkedCount = 0;
            $usedCount = 0;
            $unusedCount = 0; // Chỉ đếm, không lưu tất cả vào memory
            $maxCheck = PHP_INT_MAX; // Không giới hạn số files check
            $maxUnusedToStore = 1000; // Chỉ lưu 1000 unused images vào memory để hiển thị
            
            // Cache: Lấy tất cả ảnh đang được sử dụng
            // Logic: 1) Lấy image_ids từ products, 2) Lấy URLs từ images table, 3) Lấy từ banners, 4) Lấy từ text content
            Log::info('🔵 [DEBUG ToolsController] Loading database cache (chunked)...');
            
            $allUsedFilenames = [];
            $usedImageIds = [];
            
            // 1. Lấy tất cả image_ids từ products (quan trọng nhất!)
            $productImageIdsCount = 0;
            Product::whereNotNull('image_ids')
                ->select('id', 'image_ids')
                ->orderBy('id')
                ->chunk(1000, function ($products) use (&$usedImageIds, &$productImageIdsCount) {
                    foreach ($products as $product) {
                        if (is_array($product->image_ids)) {
                            $usedImageIds = array_merge($usedImageIds, $product->image_ids);
                            $productImageIdsCount += count($product->image_ids);
                        } elseif (is_string($product->image_ids) && !empty($product->image_ids)) {
                            $ids = json_decode($product->image_ids, true) ?? [];
                            if (is_array($ids)) {
                                $usedImageIds = array_merge($usedImageIds, $ids);
                                $productImageIdsCount += count($ids);
                            }
                        }
                    }
                });
            
            // Unique image IDs
            $usedImageIds = array_unique(array_filter($usedImageIds));
            
            Log::info('🔵 [DEBUG ToolsController] Step 1: Found image IDs from products', [
                'total_products_with_images' => Product::whereNotNull('image_ids')->count(),
                'total_image_ids_found' => $productImageIdsCount,
                'unique_image_ids' => count($usedImageIds),
                'sample_image_ids' => array_slice($usedImageIds, 0, 10),
            ]);
            
            // 2. Lấy URLs từ images table (từ image_ids của products)
            $imagesFromProductIds = [];
            $imageIdsToCheck = $usedImageIds;
            if (count($imageIdsToCheck) > 0) {
                DB::table('images')
                    ->select('id', 'url')
                    ->whereIn('id', $imageIdsToCheck)
                    ->orderBy('id')
                    ->chunk(1000, function ($images) use (&$allUsedFilenames, &$imagesFromProductIds) {
                        foreach ($images as $image) {
                            if ($image->url) {
                                $filename = $this->normalizeImageFilename($image->url);
                                if ($filename) {
                                    $allUsedFilenames[] = $filename;
                                    $imagesFromProductIds[] = [
                                        'id' => $image->id,
                                        'url' => $image->url,
                                        'filename' => $filename,
                                    ];
                                }
                            }
                        }
                    });
            }
            
            Log::info('🔵 [DEBUG ToolsController] Step 2: Images from product image_ids', [
                'image_ids_checked' => count($imageIdsToCheck),
                'images_found' => count($imagesFromProductIds),
                'sample' => array_slice($imagesFromProductIds, 0, 5),
            ]);
            
            // 3. Lấy tất cả URLs từ images table (để đảm bảo không bỏ sót)
            $allImagesFromTable = [];
            $totalImagesInTable = DB::table('images')->count();
            DB::table('images')
                ->select('id', 'url')
                ->orderBy('id')
                ->chunk(1000, function ($images) use (&$allUsedFilenames, &$allImagesFromTable) {
                    foreach ($images as $image) {
                        if ($image->url) {
                            $filename = $this->normalizeImageFilename($image->url);
                            if ($filename) {
                                $allUsedFilenames[] = $filename;
                                $allImagesFromTable[] = [
                                    'id' => $image->id,
                                    'url' => $image->url,
                                    'filename' => $filename,
                                ];
                            }
                        }
                    }
                });
            
            Log::info('🔵 [DEBUG ToolsController] Step 3: All images from images table', [
                'total_images_in_table' => $totalImagesInTable,
                'images_with_url' => count($allImagesFromTable),
                'unique_filenames' => count(array_unique(array_column($allImagesFromTable, 'filename'))),
                'sample' => array_slice($allImagesFromTable, 0, 5),
            ]);
            
            // 4. Banners - Lấy basename
            $bannerFilenames = [];
            Banner::select('image_desktop', 'image_mobile')
                ->orderBy('id')
                ->chunk(1000, function ($banners) use (&$allUsedFilenames, &$bannerFilenames) {
                    foreach ($banners as $banner) {
                        if ($banner->image_desktop) {
                            $filename = $this->normalizeImageFilename($banner->image_desktop);
                            if ($filename) {
                                $allUsedFilenames[] = $filename;
                                $bannerFilenames[] = $filename;
                            }
                        }
                        if ($banner->image_mobile) {
                            $filename = $this->normalizeImageFilename($banner->image_mobile);
                            if ($filename) {
                                $allUsedFilenames[] = $filename;
                                $bannerFilenames[] = $filename;
                            }
                        }
                    }
                });
            
            Log::info('🔵 [DEBUG ToolsController] Step 4: Banners', [
                'total_banners' => Banner::count(),
                'banner_filenames' => count($bannerFilenames),
                'sample' => array_slice(array_unique($bannerFilenames), 0, 5),
            ]);
            
            // 5. Products - Extract filenames từ description (text search)
            $productLimit = 10000;
            $productCount = 0;
            $productFilenames = [];
            DB::table('products')
                ->select('id', 'description', 'short_description')
                ->where(function($query) {
                    $query->whereNotNull('description')
                          ->orWhereNotNull('short_description');
                })
                ->orderBy('id')
                ->chunk(500, function ($products) use (&$allUsedFilenames, &$productCount, &$productFilenames, $productLimit) {
                    foreach ($products as $product) {
                        if ($productCount >= $productLimit) {
                            return false;
                        }
                        $productCount++;
                        
                        if ($product->description) {
                            preg_match_all('/[a-zA-Z0-9_-]+\.(jpg|jpeg|png|gif|webp|svg)/i', $product->description, $matches);
                            if (!empty($matches[0])) {
                                foreach ($matches[0] as $filename) {
                                    $filenameLower = strtolower($filename);
                                    $allUsedFilenames[] = $filenameLower;
                                    $productFilenames[] = $filenameLower;
                                }
                            }
                        }
                        if ($product->short_description) {
                            preg_match_all('/[a-zA-Z0-9_-]+\.(jpg|jpeg|png|gif|webp|svg)/i', $product->short_description, $matches);
                            if (!empty($matches[0])) {
                                foreach ($matches[0] as $filename) {
                                    $filenameLower = strtolower($filename);
                                    $allUsedFilenames[] = $filenameLower;
                                    $productFilenames[] = $filenameLower;
                                }
                            }
                        }
                    }
                });
            
            Log::info('🔵 [DEBUG ToolsController] Step 5: Products description', [
                'products_scanned' => $productCount,
                'filenames_found' => count($productFilenames),
                'unique_filenames' => count(array_unique($productFilenames)),
                'sample' => array_slice(array_unique($productFilenames), 0, 5),
            ]);
            
            // 6. Posts - Extract filenames từ content
            $postLimit = 5000;
            $postCount = 0;
            $postFilenames = [];
            DB::table('posts')
                ->select('id', 'content')
                ->whereNotNull('content')
                ->orderBy('id')
                ->chunk(500, function ($posts) use (&$allUsedFilenames, &$postCount, &$postFilenames, $postLimit) {
                    foreach ($posts as $post) {
                        if ($postCount >= $postLimit) {
                            return false;
                        }
                        $postCount++;
                        
                        if ($post->content) {
                            preg_match_all('/[a-zA-Z0-9_-]+\.(jpg|jpeg|png|gif|webp|svg)/i', $post->content, $matches);
                            if (!empty($matches[0])) {
                                foreach ($matches[0] as $filename) {
                                    $filenameLower = strtolower($filename);
                                    $allUsedFilenames[] = $filenameLower;
                                    $postFilenames[] = $filenameLower;
                                }
                            }
                        }
                    }
                });
            
            Log::info('🔵 [DEBUG ToolsController] Step 6: Posts content', [
                'posts_scanned' => $postCount,
                'filenames_found' => count($postFilenames),
                'unique_filenames' => count(array_unique($postFilenames)),
                'sample' => array_slice(array_unique($postFilenames), 0, 5),
            ]);
            
            // Unique và convert to array for fast lookup
            $totalBeforeUnique = count($allUsedFilenames);
            $allUsedFilenames = array_flip(array_unique($allUsedFilenames)); // Use flip for O(1) lookup
            
            // Log một số ví dụ để debug
            $sampleFilenames = array_slice(array_keys($allUsedFilenames), 0, 20);
            
            Log::info('🟢 [DEBUG ToolsController] Database cache summary', [
                'total_filenames_before_unique' => $totalBeforeUnique,
                'total_used_filenames_unique' => count($allUsedFilenames),
                'sample_filenames' => $sampleFilenames,
            ]);
            
            // Sử dụng RecursiveDirectoryIterator thay vì allFiles() để tránh load hết vào memory
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($clothesPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            
            Log::info('🔵 [DEBUG ToolsController] Starting file iteration...');
            
            foreach ($iterator as $file) {
                // Dừng nếu đã kiểm tra đủ số lượng
                if ($checkedCount >= $maxCheck) {
                    Log::info('🟡 [DEBUG ToolsController] Reached max check limit', ['max' => $maxCheck]);
                    break;
                }
                
                // Không dừng, chỉ giới hạn số lượng lưu vào memory
                
                if (!$file->isFile()) {
                    continue;
                }
                
                $extension = strtolower($file->getExtension());
                
                if (!in_array($extension, $imageExtensions)) {
                    continue;
                }
                
                $relativePath = str_replace(public_path(), '', $file->getPathname());
                $relativePath = str_replace('\\', '/', $relativePath);
                
                // Bỏ qua folder 40x40
                if (str_contains($relativePath, '/40x40/')) {
                    continue;
                }
                
                $fileName = $file->getFilename();
                $fileNameLower = strtolower($fileName);
                
                // Bỏ qua file no-image.webp (file mặc định)
                if ($fileNameLower === 'no-image.webp') {
                    continue;
                }
                
                $checkedCount++;
                
                // Kiểm tra trong cache - O(1) lookup với array_flip
                $isUsed = isset($allUsedFilenames[$fileNameLower]);
                
                // Debug: Log chi tiết cho 20 file đầu tiên và một số file random
                if ($checkedCount <= 20 || ($checkedCount % 10000 === 0 && $checkedCount <= 100000)) {
                    Log::info('🔵 [DEBUG ToolsController] Checking file #' . $checkedCount, [
                        'filename' => $fileName,
                        'filename_lower' => $fileNameLower,
                        'relative_path' => $relativePath,
                        'is_used' => $isUsed,
                        'cache_has_key' => isset($allUsedFilenames[$fileNameLower]),
                        'file_exists' => $file->isFile(),
                        'file_size' => $file->getSize(),
                        'cache_size' => count($allUsedFilenames),
                    ]);
                }
                
                // Debug: Nếu file không có trong cache, log để kiểm tra
                if (!$isUsed) {
                    // Log 100 unused files đầu tiên để debug
                    if ($unusedCount <= 100) {
                        // Kiểm tra xem có filename tương tự trong cache không
                        $similarInCache = [];
                        foreach (array_keys($allUsedFilenames) as $cachedFilename) {
                            if (stripos($cachedFilename, $fileNameLower) !== false || stripos($fileNameLower, $cachedFilename) !== false) {
                                $similarInCache[] = $cachedFilename;
                                if (count($similarInCache) >= 5) {
                                    break;
                                }
                            }
                        }
                        
                        Log::info('❌ [DEBUG ToolsController] UNUSED FILE FOUND (not in cache)', [
                            'filename' => $fileName,
                            'filename_lower' => $fileNameLower,
                            'relative_path' => $relativePath,
                            'similar_in_cache' => $similarInCache,
                            'unused_count' => $unusedCount + 1,
                        ]);
                    }
                }
                
                if ($isUsed) {
                    $usedCount++;
                    continue;
                }
                
                // Ảnh không được sử dụng
                $unusedCount++;
                $fileSize = $file->getSize();
                $totalSize += $fileSize;
                
                // Chỉ lưu vào array nếu chưa đủ maxUnusedToStore
                if (count($unusedImages) < $maxUnusedToStore) {
                    $unusedImages[] = [
                        'path' => $relativePath,
                        'name' => $fileName,
                        'size' => $this->formatBytes($fileSize),
                        'size_bytes' => $fileSize,
                        'url' => asset($relativePath),
                    ];
                    
                    // Log chi tiết cho 50 unused images đầu tiên
                    if ($unusedCount <= 50) {
                        Log::info('❌ [DEBUG ToolsController] UNUSED IMAGE FOUND!', [
                            'filename' => $fileName,
                            'filename_lower' => $fileNameLower,
                            'relative_path' => $relativePath,
                            'file_size' => $this->formatBytes($fileSize),
                            'unused_count' => $unusedCount,
                        ]);
                    }
                } else {
                    // Chỉ log mỗi 100 unused images để không spam log
                    if ($unusedCount % 100 === 0) {
                        Log::info('❌ [DEBUG ToolsController] More unused images found', [
                            'unused_count' => $unusedCount,
                            'current_file' => $fileName,
                            'total_size' => $this->formatBytes($totalSize),
                        ]);
                    }
                }
                
                // Giải phóng memory mỗi 50 file (tăng tần suất)
                if ($checkedCount % 50 === 0) {
                    gc_collect_cycles();
                }
                
                // Log progress mỗi 1000 files để theo dõi
                if ($checkedCount % 1000 === 0) {
                    Log::info('📊 [DEBUG ToolsController] Progress', [
                        'checked' => $checkedCount,
                        'used' => $usedCount,
                        'unused' => $unusedCount,
                        'unused_stored' => count($unusedImages),
                        'total_size' => $this->formatBytes($totalSize),
                        'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                    ]);
                }
            }
            
            // Log tổng kết chi tiết
            $unusedSample = array_slice($unusedImages, 0, 20);
            
            // Thống kê: So sánh số files trong folder vs số filenames trong cache
            // Lấy một số sample files từ folder để verify
            $sampleFilesFromFolder = [];
            $iteratorVerify = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($clothesPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            
            $verifyCount = 0;
            foreach ($iteratorVerify as $file) {
                if (!$file->isFile()) continue;
                $extension = strtolower($file->getExtension());
                if (!in_array($extension, $imageExtensions)) continue;
                
                $relativePath = str_replace(public_path(), '', $file->getPathname());
                $relativePath = str_replace('\\', '/', $relativePath);
                if (str_contains($relativePath, '/40x40/')) continue;
                
                $fileName = $file->getFilename();
                $fileNameLower = strtolower($fileName);
                if ($fileNameLower === 'no-image.webp') continue;
                
                $verifyCount++;
                if ($verifyCount <= 50) {
                    $isInCache = isset($allUsedFilenames[$fileNameLower]);
                    $sampleFilesFromFolder[] = [
                        'filename' => $fileName,
                        'filename_lower' => $fileNameLower,
                        'in_cache' => $isInCache,
                    ];
                }
                
                if ($verifyCount >= 50) break;
            }
            
            Log::info('🟢 [DEBUG ToolsController] ===== IMAGE SCAN COMPLETED =====', [
                'total_files_checked' => $checkedCount,
                'files_used' => $usedCount,
                'files_unused_total' => $unusedCount,
                'files_unused_stored' => count($unusedImages),
                'unused_total_size' => $this->formatBytes($totalSize),
                'unused_sample' => $unusedSample,
                'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'total_used_filenames_in_cache' => count($allUsedFilenames),
                'sample_files_from_folder' => $sampleFilesFromFolder,
            ]);
            
            // Log cảnh báo nếu không tìm thấy unused images
            if ($unusedCount === 0 && $checkedCount > 0) {
                Log::warning('⚠️ [DEBUG ToolsController] NO UNUSED IMAGES FOUND!', [
                    'checked_files' => $checkedCount,
                    'used_files' => $usedCount,
                    'total_used_filenames_in_cache' => count($allUsedFilenames),
                    'possible_issue' => 'All files are marked as used. Check normalization logic.',
                    'sample_files_verification' => $sampleFilesFromFolder,
                ]);
                
                // Thử tìm các files không có trong cache để verify
                $filesNotInCache = [];
                $iteratorTest = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($clothesPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::SELF_FIRST
                );
                
                $testCount = 0;
                foreach ($iteratorTest as $file) {
                    if (!$file->isFile()) continue;
                    $extension = strtolower($file->getExtension());
                    if (!in_array($extension, $imageExtensions)) continue;
                    
                    $relativePath = str_replace(public_path(), '', $file->getPathname());
                    $relativePath = str_replace('\\', '/', $relativePath);
                    if (str_contains($relativePath, '/40x40/')) continue;
                    
                    $fileName = $file->getFilename();
                    $fileNameLower = strtolower($fileName);
                    if ($fileNameLower === 'no-image.webp') continue;
                    
                    $testCount++;
                    
                    // Kiểm tra xem file này có trong cache không
                    if (!isset($allUsedFilenames[$fileNameLower])) {
                        $filesNotInCache[] = [
                            'filename' => $fileName,
                            'filename_lower' => $fileNameLower,
                            'path' => $relativePath,
                        ];
                        
                        // Chỉ lấy 20 files đầu tiên không có trong cache
                        if (count($filesNotInCache) >= 20) {
                            break;
                        }
                    }
                    
                    // Chỉ test 1000 files đầu tiên để không tốn thời gian
                    if ($testCount >= 1000) {
                        break;
                    }
                }
                
                if (!empty($filesNotInCache)) {
                    Log::error('🔴 [DEBUG ToolsController] FOUND FILES NOT IN CACHE!', [
                        'count' => count($filesNotInCache),
                        'files' => $filesNotInCache,
                        'issue' => 'These files exist in folder but are NOT in cache. They should be marked as unused!',
                        'possible_cause' => 'Logic error: Files not in cache but marked as used during scan.',
                    ]);
                } else {
                    Log::info('✅ [DEBUG ToolsController] Verification: All tested files are in cache', [
                        'tested_files' => $testCount,
                        'files_not_in_cache' => 0,
                        'conclusion' => 'All files in folder appear to be in database cache. No unused images found.',
                    ]);
                }
            } else if ($unusedCount > 0) {
                Log::info('✅ [DEBUG ToolsController] FOUND UNUSED IMAGES!', [
                    'total_unused' => $unusedCount,
                    'stored_for_display' => count($unusedImages),
                    'note' => $unusedCount > $maxUnusedToStore ? "Only showing first {$maxUnusedToStore} unused images. Total: {$unusedCount}" : 'All unused images are shown.',
                ]);
            }
            
            return response()->json([
                'success' => true,
                'unused_count' => $unusedCount, // Tổng số unused thực tế
                'unused_images' => $unusedImages, // Chỉ lưu maxUnusedToStore images
                'total_size' => $this->formatBytes($totalSize),
                'total_size_bytes' => $totalSize,
                'checked_count' => $checkedCount,
                'used_count' => $usedCount,
                'has_more' => $unusedCount > $maxUnusedToStore, // Có nhiều hơn số lượng hiển thị
                'limit' => $maxUnusedToStore,
                'note' => $unusedCount > $maxUnusedToStore ? "Chỉ hiển thị {$maxUnusedToStore} ảnh đầu tiên trong tổng số {$unusedCount} ảnh không được sử dụng." : null,
            ]);
        } catch (\Exception $e) {
            Log::error('🔴 [DEBUG ToolsController] Exception in getUnusedImagesStats', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Xóa ảnh sản phẩm không được sử dụng
     * Tối ưu: Sử dụng iterator, cache database, xử lý theo batch
     */
    public function deleteUnusedImages(Request $request): JsonResponse
    {
        try {
            // Kiểm tra an toàn server
            $this->checkServerSafety();
            
            // Tăng timeout và memory limit
            set_time_limit(300); // 5 phút
            ini_set('memory_limit', '512M');
            
            Log::info('🔵 [DEBUG ToolsController] deleteUnusedImages - Starting');
            
            $clothesPath = public_path('clients/assets/img/clothes');
            
            if (!File::exists($clothesPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thư mục không tồn tại: ' . $clothesPath,
                ], 404);
            }
            
            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
            $deletedImages = [];
            $deletedSize = 0;
            $deletedCount = 0;
            $maxDelete = 1000; // Giới hạn số file xóa mỗi lần để tránh timeout
            
            // Load cache giống như getUnusedImagesStats() - tái sử dụng logic
            $allUsedFilenames = [];
            $usedImageIds = [];
            
            // 1. Lấy image_ids từ products
            Product::whereNotNull('image_ids')
                ->select('image_ids')
                ->orderBy('id')
                ->chunk(1000, function ($products) use (&$usedImageIds) {
                    foreach ($products as $product) {
                        if (is_array($product->image_ids)) {
                            $usedImageIds = array_merge($usedImageIds, $product->image_ids);
                        } elseif (is_string($product->image_ids) && !empty($product->image_ids)) {
                            $ids = json_decode($product->image_ids, true) ?? [];
                            if (is_array($ids)) {
                                $usedImageIds = array_merge($usedImageIds, $ids);
                            }
                        }
                    }
                });
            
            $usedImageIds = array_unique(array_filter($usedImageIds));
            
            // 2. Lấy URLs từ images table
            if (count($usedImageIds) > 0) {
                DB::table('images')
                    ->select('url')
                    ->whereIn('id', $usedImageIds)
                    ->orderBy('id')
                    ->chunk(1000, function ($images) use (&$allUsedFilenames) {
                        foreach ($images as $image) {
                            if ($image->url) {
                                $filename = $this->normalizeImageFilename($image->url);
                                if ($filename) {
                                    $allUsedFilenames[] = $filename;
                                }
                            }
                        }
                    });
            }
            
            // 3. Lấy tất cả URLs từ images table
            DB::table('images')
                ->select('url')
                ->orderBy('id')
                ->chunk(1000, function ($images) use (&$allUsedFilenames) {
                    foreach ($images as $image) {
                        if ($image->url) {
                            $filename = $this->normalizeImageFilename($image->url);
                            if ($filename) {
                                $allUsedFilenames[] = $filename;
                            }
                        }
                    }
                });
            
            // 4. Banners
            Banner::select('image_desktop', 'image_mobile')
                ->orderBy('id')
                ->chunk(1000, function ($banners) use (&$allUsedFilenames) {
                    foreach ($banners as $banner) {
                        if ($banner->image_desktop) {
                            $filename = $this->normalizeImageFilename($banner->image_desktop);
                            if ($filename) {
                                $allUsedFilenames[] = $filename;
                            }
                        }
                        if ($banner->image_mobile) {
                            $filename = $this->normalizeImageFilename($banner->image_mobile);
                            if ($filename) {
                                $allUsedFilenames[] = $filename;
                            }
                        }
                    }
                });
            
            $allUsedFilenames = array_flip(array_unique($allUsedFilenames));
            
            // Sử dụng iterator thay vì allFiles() để tránh load hết vào memory
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($clothesPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($iterator as $file) {
                // Dừng nếu đã xóa đủ số lượng
                if ($deletedCount >= $maxDelete) {
                    Log::info('🟡 [DEBUG ToolsController] Reached max delete limit', ['max' => $maxDelete]);
                    break;
                }
                
                if (!$file->isFile()) {
                    continue;
                }
                
                $extension = strtolower($file->getExtension());
                
                if (!in_array($extension, $imageExtensions)) {
                    continue;
                }
                
                $relativePath = str_replace(public_path(), '', $file->getPathname());
                $relativePath = str_replace('\\', '/', $relativePath);
                
                // Bỏ qua folder 40x40
                if (str_contains($relativePath, '/40x40/')) {
                    continue;
                }
                
                $fileName = $file->getFilename();
                $fileNameLower = strtolower($fileName);
                
                // Bỏ qua file no-image.webp (file mặc định)
                if ($fileNameLower === 'no-image.webp') {
                    continue;
                }
                
                // Kiểm tra trong cache - O(1) lookup
                $isUsed = isset($allUsedFilenames[$fileNameLower]);
                
                if ($isUsed) {
                    continue;
                }
                
                // Xóa ảnh không được sử dụng
                $fileSize = $file->getSize();
                
                if (File::delete($file->getPathname())) {
                    $deletedSize += $fileSize;
                    $deletedCount++;
                    
                    $deletedImages[] = [
                        'path' => $relativePath,
                        'name' => $fileName,
                        'size' => $this->formatBytes($fileSize),
                    ];
                }
                
                // Giải phóng memory mỗi 50 file
                if ($deletedCount % 50 === 0) {
                    gc_collect_cycles();
                }
            }
            
            Log::info('🟢 [DEBUG ToolsController] Images deleted', [
                'deleted_count' => $deletedCount,
                'deleted_size' => $this->formatBytes($deletedSize),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "Đã xóa thành công {$deletedCount} ảnh, tiết kiệm {$this->formatBytes($deletedSize)}",
                'deleted_count' => $deletedCount,
                'deleted_size' => $this->formatBytes($deletedSize),
                'deleted_images' => $deletedImages,
                'has_more' => $deletedCount >= $maxDelete,
            ]);
        } catch (\Exception $e) {
            Log::error('🔴 [DEBUG ToolsController] Exception in deleteUnusedImages', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Batch update tag usage counts - Tránh N+1 queries
     * Sử dụng CASE WHEN để update nhiều tags trong 1 query
     */
    private function batchUpdateTagUsageCounts(array $tagUsageCounts): void
    {
        if (empty($tagUsageCounts)) {
            return;
        }
        
        // Chia nhỏ thành batch 100 tags mỗi lần để tránh query quá dài
        $batches = array_chunk($tagUsageCounts, 100, true);
        
        foreach ($batches as $batch) {
            $caseStatements = [];
            $tagIds = array_keys($batch);
            
            foreach ($batch as $tagId => $count) {
                $caseStatements[] = "WHEN {$tagId} THEN usage_count + {$count}";
            }
            
            $caseSql = implode(' ', $caseStatements);
            $tagIdsStr = implode(',', $tagIds);
            
            DB::statement("
                UPDATE tags 
                SET usage_count = CASE id 
                    {$caseSql}
                END
                WHERE id IN ({$tagIdsStr})
            ");
        }
    }

    /**
     * Normalize image filename từ URL
     * Xử lý nhiều format: full path, relative path, chỉ filename
     */
    private function normalizeImageFilename(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }
        
        // Loại bỏ leading slash
        $url = ltrim($url, '/');
        
        // Loại bỏ các prefix path thường gặp
        $url = preg_replace('#^clients/assets/img/clothes/#', '', $url);
        $url = preg_replace('#^admins/img/#', '', $url);
        $url = preg_replace('#^public/#', '', $url);
        
        // Lấy basename (filename cuối cùng)
        $filename = basename($url);
        
        // Loại bỏ query string nếu có
        $filename = preg_replace('/\?.*$/', '', $filename);
        
        return $filename ? strtolower($filename) : null;
    }

    /**
     * Format bytes thành dạng dễ đọc
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Xóa cache/log files
     * Xóa tất cả log files theo ngày, trừ các file đứng một mình và giữ lại 7 log gần nhất
     */
    public function clearCache(Request $request): JsonResponse
    {
        try {
            Log::info('🔵 [ToolsController] clearCache - Starting');
            
            $logsPath = storage_path('logs');
            
            if (!is_dir($logsPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thư mục logs không tồn tại',
                ], 404);
            }
            
            // Lấy tất cả files trong thư mục logs
            $allFiles = File::files($logsPath);
            
            // Phân loại files
            $dateLogFiles = []; // Files theo pattern laravel-YYYY-MM-DD.log
            $standaloneFiles = []; // Files đứng một mình (browser.log, media.log, etc.)
            
            foreach ($allFiles as $file) {
                $fileName = $file->getFilename();
                
                // Kiểm tra xem có phải file log theo ngày không (pattern: laravel-YYYY-MM-DD.log)
                if (preg_match('/^laravel-(\d{4}-\d{2}-\d{2})\.log$/', $fileName, $matches)) {
                    $date = $matches[1];
                    $dateLogFiles[] = [
                        'file' => $file,
                        'date' => $date,
                        'filename' => $fileName,
                        'path' => $file->getPathname(),
                        'size' => $file->getSize(),
                    ];
                } else {
                    // File đứng một mình (không theo pattern ngày)
                    $standaloneFiles[] = [
                        'file' => $file,
                        'filename' => $fileName,
                        'path' => $file->getPathname(),
                        'size' => $file->getSize(),
                    ];
                }
            }
            
            // Sắp xếp date log files theo ngày (mới nhất trước)
            usort($dateLogFiles, function ($a, $b) {
                return strcmp($b['date'], $a['date']); // Descending order
            });
            
            $totalDateLogs = count($dateLogFiles);
            $keepCount = 7; // Giữ lại 7 log gần nhất
            $filesToDelete = [];
            $filesToKeep = [];
            $totalSizeDeleted = 0;
            
            // Chia thành files cần giữ và files cần xóa
            foreach ($dateLogFiles as $index => $logFile) {
                if ($index < $keepCount) {
                    $filesToKeep[] = $logFile;
                } else {
                    $filesToDelete[] = $logFile;
                    $totalSizeDeleted += $logFile['size'];
                }
            }
            
            // Xóa các files cũ
            $deletedCount = 0;
            $deletedFiles = [];
            
            foreach ($filesToDelete as $logFile) {
                try {
                    if (File::delete($logFile['path'])) {
                        $deletedCount++;
                        $deletedFiles[] = [
                            'filename' => $logFile['filename'],
                            'date' => $logFile['date'],
                            'size' => $this->formatBytes($logFile['size']),
                        ];
                    }
                } catch (\Exception $e) {
                    Log::error('🔴 [ToolsController] Error deleting log file', [
                        'file' => $logFile['filename'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            // Thống kê
            $keptFiles = array_map(function ($file) {
                return [
                    'filename' => $file['filename'],
                    'date' => $file['date'],
                    'size' => $this->formatBytes($file['size']),
                ];
            }, $filesToKeep);
            
            $standaloneFilesInfo = array_map(function ($file) {
                return [
                    'filename' => $file['filename'],
                    'size' => $this->formatBytes($file['size']),
                ];
            }, $standaloneFiles);
            
            Log::info('🟢 [ToolsController] clearCache completed', [
                'total_date_logs' => $totalDateLogs,
                'kept_logs' => count($filesToKeep),
                'deleted_logs' => $deletedCount,
                'standalone_files' => count($standaloneFiles),
                'total_size_deleted' => $this->formatBytes($totalSizeDeleted),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "Đã xóa thành công {$deletedCount} log files, tiết kiệm {$this->formatBytes($totalSizeDeleted)}",
                'stats' => [
                    'total_date_logs' => $totalDateLogs,
                    'kept_logs' => count($filesToKeep),
                    'deleted_logs' => $deletedCount,
                    'standalone_files' => count($standaloneFiles),
                    'total_size_deleted' => $this->formatBytes($totalSizeDeleted),
                ],
                'kept_files' => $keptFiles,
                'deleted_files' => array_slice($deletedFiles, 0, 20), // Chỉ hiển thị 20 files đầu tiên
                'standalone_files' => $standaloneFilesInfo,
            ]);
            
        } catch (\Exception $e) {
            Log::error('🔴 [ToolsController] clearCache error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa cache: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear Application Cache (config, route, view, application)
     */
    public function clearApplicationCache(Request $request): JsonResponse
    {
        try {
            Log::info('🔵 [ToolsController] clearApplicationCache - Starting');
            
            $cleared = [];
            $errors = [];
            
            // Clear config cache
            try {
                Artisan::call('config:clear');
                $cleared[] = 'Config cache';
            } catch (\Exception $e) {
                $errors[] = 'Config cache: ' . $e->getMessage();
            }
            
            // Clear route cache
            try {
                Artisan::call('route:clear');
                $cleared[] = 'Route cache';
            } catch (\Exception $e) {
                $errors[] = 'Route cache: ' . $e->getMessage();
            }
            
            // Clear view cache
            try {
                Artisan::call('view:clear');
                $cleared[] = 'View cache';
            } catch (\Exception $e) {
                $errors[] = 'View cache: ' . $e->getMessage();
            }
            
            // Clear application cache
            try {
                Artisan::call('cache:clear');
                $cleared[] = 'Application cache';
            } catch (\Exception $e) {
                $errors[] = 'Application cache: ' . $e->getMessage();
            }
            
            Log::info('🟢 [ToolsController] clearApplicationCache completed', [
                'cleared' => $cleared,
                'errors' => $errors,
            ]);
            
            if (!empty($errors)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Đã xóa cache: ' . implode(', ', $cleared) . '. Một số lỗi: ' . implode(', ', $errors),
                    'cleared' => $cleared,
                    'errors' => $errors,
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Đã xóa thành công tất cả cache: ' . implode(', ', $cleared),
                'cleared' => $cleared,
            ]);
            
        } catch (\Exception $e) {
            Log::error('🔴 [ToolsController] clearApplicationCache error', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa cache: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clean Temporary Files
     * Xóa file tạm trong storage/app/exports, storage/app/imports, storage/app/temp
     */
    public function cleanTemporaryFiles(Request $request): JsonResponse
    {
        try {
            Log::info('🔵 [ToolsController] cleanTemporaryFiles - Starting');
            
            $daysOld = (int) ($request->input('days', 7)); // Mặc định 7 ngày
            $cutoffTime = time() - ($daysOld * 24 * 60 * 60);
            
            $directories = [
                storage_path('app/exports'),
                storage_path('app/imports'),
                storage_path('app/temp'),
                storage_path('app/tmp'),
            ];
            
            $deletedFiles = [];
            $deletedCount = 0;
            $totalSize = 0;
            $errors = [];
            
            foreach ($directories as $dir) {
                if (!is_dir($dir)) {
                    continue;
                }
                
                try {
                    $files = File::allFiles($dir);
                    
                    foreach ($files as $file) {
                        $fileTime = $file->getMTime();
                        
                        // Xóa file cũ hơn cutoffTime
                        if ($fileTime < $cutoffTime) {
                            try {
                                $fileSize = $file->getSize();
                                if (File::delete($file->getPathname())) {
                                    $deletedCount++;
                                    $totalSize += $fileSize;
                                    $deletedFiles[] = [
                                        'path' => str_replace(storage_path('app'), 'storage/app', $file->getPathname()),
                                        'size' => $this->formatBytes($fileSize),
                                        'age_days' => round((time() - $fileTime) / (24 * 60 * 60), 1),
                                    ];
                                }
                            } catch (\Exception $e) {
                                $errors[] = $file->getPathname() . ': ' . $e->getMessage();
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $errors[] = $dir . ': ' . $e->getMessage();
                }
            }
            
            Log::info('🟢 [ToolsController] cleanTemporaryFiles completed', [
                'deleted_count' => $deletedCount,
                'total_size' => $this->formatBytes($totalSize),
                'days_old' => $daysOld,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "Đã xóa {$deletedCount} file tạm (cũ hơn {$daysOld} ngày), tiết kiệm {$this->formatBytes($totalSize)}",
                'stats' => [
                    'deleted_count' => $deletedCount,
                    'total_size' => $this->formatBytes($totalSize),
                    'days_old' => $daysOld,
                ],
                'deleted_files' => array_slice($deletedFiles, 0, 20), // Chỉ hiển thị 20 files đầu tiên
                'errors' => $errors,
            ]);
            
        } catch (\Exception $e) {
            Log::error('🔴 [ToolsController] cleanTemporaryFiles error', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa file tạm: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Database Optimization
     * Optimize/Analyze tables
     */
    public function optimizeDatabase(Request $request): JsonResponse
    {
        try {
            Log::info('🔵 [ToolsController] optimizeDatabase - Starting');
            
            $action = $request->input('action', 'analyze'); // analyze hoặc optimize
            
            $tables = DB::select('SHOW TABLES');
            $tableKey = 'Tables_in_' . DB::getDatabaseName();
            
            $results = [];
            $totalSize = 0;
            
            foreach ($tables as $table) {
                $tableName = $table->$tableKey;
                
                try {
                    if ($action === 'optimize') {
                        DB::statement("OPTIMIZE TABLE `{$tableName}`");
                        $actionText = 'optimized';
                    } else {
                        DB::statement("ANALYZE TABLE `{$tableName}`");
                        $actionText = 'analyzed';
                    }
                    
                    // Lấy thông tin kích thước table
                    $tableInfo = DB::selectOne("
                        SELECT 
                            ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
                            table_rows
                        FROM information_schema.TABLES 
                        WHERE table_schema = ? AND table_name = ?
                    ", [DB::getDatabaseName(), $tableName]);
                    
                    $size = $tableInfo->size_mb ?? 0;
                    $rows = $tableInfo->table_rows ?? 0;
                    $totalSize += $size;
                    
                    $results[] = [
                        'table' => $tableName,
                        'size_mb' => round($size, 2),
                        'rows' => number_format($rows),
                        'status' => $actionText,
                    ];
                } catch (\Exception $e) {
                    $results[] = [
                        'table' => $tableName,
                        'status' => 'error',
                        'error' => $e->getMessage(),
                    ];
                }
            }
            
            Log::info('🟢 [ToolsController] optimizeDatabase completed', [
                'action' => $action,
                'tables_count' => count($results),
                'total_size_mb' => round($totalSize, 2),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "Đã {$actionText} " . count($results) . " tables. Tổng kích thước: " . round($totalSize, 2) . " MB",
                'action' => $action,
                'stats' => [
                    'tables_count' => count($results),
                    'total_size_mb' => round($totalSize, 2),
                ],
                'results' => $results,
            ]);
            
        } catch (\Exception $e) {
            Log::error('🔴 [ToolsController] optimizeDatabase error', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi optimize database: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * System Information
     * Hiển thị thông tin server
     */
    public function getSystemInfo(): JsonResponse
    {
        try {
            $info = [
                'php' => [
                    'version' => PHP_VERSION,
                    'memory_limit' => ini_get('memory_limit'),
                    'max_execution_time' => ini_get('max_execution_time'),
                    'upload_max_filesize' => ini_get('upload_max_filesize'),
                    'post_max_size' => ini_get('post_max_size'),
                ],
                'laravel' => [
                    'version' => app()->version(),
                    'environment' => app()->environment(),
                    'debug' => config('app.debug'),
                ],
                'server' => [
                    'os' => PHP_OS,
                    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                ],
                'database' => [
                    'driver' => DB::connection()->getDriverName(),
                    'database' => DB::getDatabaseName(),
                ],
                'disk' => [],
                'memory' => [
                    'current_usage' => $this->formatBytes(memory_get_usage(true)),
                    'peak_usage' => $this->formatBytes(memory_get_peak_usage(true)),
                ],
            ];
            
            // Disk usage
            $storagePath = storage_path();
            $publicPath = public_path();
            
            if (function_exists('disk_total_space') && function_exists('disk_free_space')) {
                $totalSpace = disk_total_space($storagePath);
                $freeSpace = disk_free_space($storagePath);
                $usedSpace = $totalSpace - $freeSpace;
                
                $info['disk'] = [
                    'total' => $this->formatBytes($totalSpace),
                    'used' => $this->formatBytes($usedSpace),
                    'free' => $this->formatBytes($freeSpace),
                    'usage_percent' => round(($usedSpace / $totalSpace) * 100, 2),
                ];
            }
            
            return response()->json([
                'success' => true,
                'info' => $info,
            ]);
            
        } catch (\Exception $e) {
            Log::error('🔴 [ToolsController] getSystemInfo error', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy thông tin hệ thống: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear Old Sessions
     * Xóa sessions cũ trong database/files
     */
    public function clearOldSessions(Request $request): JsonResponse
    {
        try {
            Log::info('🔵 [ToolsController] clearOldSessions - Starting');
            
            $daysOld = (int) ($request->input('days', 30)); // Mặc định 30 ngày
            $cutoffTime = time() - ($daysOld * 24 * 60 * 60);
            
            $deletedCount = 0;
            $errors = [];
            
            // Xóa sessions trong database (nếu dùng database driver)
            $sessionDriver = config('session.driver');
            
            if ($sessionDriver === 'database') {
                try {
                    $deletedCount = DB::table('sessions')
                        ->where('last_activity', '<', $cutoffTime)
                        ->delete();
                } catch (\Exception $e) {
                    $errors[] = 'Database sessions: ' . $e->getMessage();
                }
            } elseif ($sessionDriver === 'file') {
                // Xóa sessions trong files
                $sessionsPath = storage_path('framework/sessions');
                
                if (is_dir($sessionsPath)) {
                    try {
                        $files = File::files($sessionsPath);
                        
                        foreach ($files as $file) {
                            $fileTime = $file->getMTime();
                            
                            if ($fileTime < $cutoffTime) {
                                try {
                                    if (File::delete($file->getPathname())) {
                                        $deletedCount++;
                                    }
                                } catch (\Exception $e) {
                                    $errors[] = $file->getPathname() . ': ' . $e->getMessage();
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        $errors[] = 'File sessions: ' . $e->getMessage();
                    }
                }
            }
            
            Log::info('🟢 [ToolsController] clearOldSessions completed', [
                'deleted_count' => $deletedCount,
                'days_old' => $daysOld,
                'driver' => $sessionDriver,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "Đã xóa {$deletedCount} sessions cũ (cũ hơn {$daysOld} ngày)",
                'stats' => [
                    'deleted_count' => $deletedCount,
                    'days_old' => $daysOld,
                    'driver' => $sessionDriver,
                ],
                'errors' => $errors,
            ]);
            
        } catch (\Exception $e) {
            Log::error('🔴 [ToolsController] clearOldSessions error', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa sessions: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Analyze Disk Usage
     * Phân tích dung lượng đĩa và tìm các thư mục/file chiếm nhiều dung lượng nhất
     */
    public function analyzeDiskUsage(): JsonResponse
    {
        try {
            set_time_limit(300);
            ini_set('memory_limit', '512M');
            
            Log::info('🔵 [ToolsController] analyzeDiskUsage - Starting');
            
            $basePath = base_path();
            $directories = [
                'storage' => storage_path(),
                'vendor' => base_path('vendor'),
                'public/clients/assets' => public_path('clients/assets'),
                'public/admins' => public_path('admins'),
                'node_modules' => base_path('node_modules'),
                'database' => database_path(),
                'bootstrap/cache' => base_path('bootstrap/cache'),
            ];
            
            $results = [];
            $totalSize = 0;
            
            foreach ($directories as $name => $path) {
                if (!is_dir($path) && !is_file($path)) {
                    continue;
                }
                
                try {
                    $size = $this->getDirectorySize($path);
                    $totalSize += $size;
                    
                    $results[] = [
                        'name' => $name,
                        'path' => str_replace($basePath . DIRECTORY_SEPARATOR, '', $path),
                        'size' => $size,
                        'size_formatted' => $this->formatBytes($size),
                        'percentage' => 0, // Sẽ tính sau
                    ];
                } catch (\Exception $e) {
                    Log::warning('🔴 [ToolsController] analyzeDiskUsage - Error scanning', [
                        'path' => $path,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            // Sắp xếp theo dung lượng giảm dần
            usort($results, function($a, $b) {
                return $b['size'] <=> $a['size'];
            });
            
            // Tính phần trăm
            foreach ($results as &$result) {
                if ($totalSize > 0) {
                    $result['percentage'] = round(($result['size'] / $totalSize) * 100, 2);
                }
            }
            
            // Lấy top 10 thư mục con lớn nhất trong storage
            $storageTopDirs = [];
            if (is_dir(storage_path())) {
                try {
                    $storageTopDirs = $this->getTopDirectories(storage_path(), 10);
                } catch (\Exception $e) {
                    Log::warning('🔴 [ToolsController] analyzeDiskUsage - Error getting storage top dirs', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            // Lấy top 10 file lớn nhất trong storage
            $storageTopFiles = [];
            if (is_dir(storage_path())) {
                try {
                    $storageTopFiles = $this->getTopFiles(storage_path(), 10);
                } catch (\Exception $e) {
                    Log::warning('🔴 [ToolsController] analyzeDiskUsage - Error getting storage top files', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            // Phân tích chi tiết storage/framework
            $frameworkDetails = [];
            $frameworkPath = storage_path('framework');
            if (is_dir($frameworkPath)) {
                try {
                    $frameworkSubDirs = $this->getTopDirectories($frameworkPath, 20);
                    $frameworkDetails = [
                        'total_size' => $this->getDirectorySize($frameworkPath),
                        'subdirectories' => $frameworkSubDirs,
                    ];
                } catch (\Exception $e) {
                    Log::warning('🔴 [ToolsController] analyzeDiskUsage - Error getting framework details', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            Log::info('🟢 [ToolsController] analyzeDiskUsage completed', [
                'total_size' => $this->formatBytes($totalSize),
                'directories_count' => count($results),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Đã phân tích dung lượng đĩa thành công',
                'stats' => [
                    'total_size' => $totalSize,
                    'total_size_formatted' => $this->formatBytes($totalSize),
                    'directories_count' => count($results),
                ],
                'directories' => $results,
                'storage_top_directories' => $storageTopDirs,
                'storage_top_files' => $storageTopFiles,
                'framework_details' => $frameworkDetails,
            ]);
            
        } catch (\Exception $e) {
            Log::error('🔴 [ToolsController] analyzeDiskUsage error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi phân tích dung lượng đĩa: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Tính tổng dung lượng của một thư mục
     */
    private function getDirectorySize(string $path): int
    {
        $size = 0;
        
        if (is_file($path)) {
            return filesize($path);
        }
        
        if (!is_dir($path)) {
            return 0;
        }
        
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            
            $count = 0;
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                    $count++;
                    
                    // Giới hạn để tránh quá lâu
                    if ($count % 1000 === 0) {
                        gc_collect_cycles();
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('🔴 [ToolsController] getDirectorySize error', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
        }
        
        return $size;
    }
    
    /**
     * Lấy top N thư mục lớn nhất trong một thư mục
     */
    private function getTopDirectories(string $path, int $topN = 10): array
    {
        $directories = [];
        
        try {
            $iterator = new \DirectoryIterator($path);
            
            foreach ($iterator as $file) {
                if ($file->isDot() || !$file->isDir()) {
                    continue;
                }
                
                try {
                    $dirPath = $file->getPathname();
                    $size = $this->getDirectorySize($dirPath);
                    
                    $directories[] = [
                        'name' => $file->getFilename(),
                        'path' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $dirPath),
                        'size' => $size,
                        'size_formatted' => $this->formatBytes($size),
                    ];
                } catch (\Exception $e) {
                    // Bỏ qua lỗi
                }
            }
            
            // Sắp xếp theo dung lượng giảm dần
            usort($directories, function($a, $b) {
                return $b['size'] <=> $a['size'];
            });
            
            // Lấy top N
            return array_slice($directories, 0, $topN);
        } catch (\Exception $e) {
            Log::warning('🔴 [ToolsController] getTopDirectories error', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
        }
        
        return [];
    }
    
    /**
     * Lấy top N file lớn nhất trong một thư mục (đệ quy)
     */
    private function getTopFiles(string $path, int $topN = 10): array
    {
        $files = [];
        
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            
            $count = 0;
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $files[] = [
                        'name' => $file->getFilename(),
                        'path' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file->getPathname()),
                        'size' => $file->getSize(),
                        'size_formatted' => $this->formatBytes($file->getSize()),
                    ];
                    
                    $count++;
                    if ($count >= 1000) { // Giới hạn số file quét để tránh quá lâu
                        break;
                    }
                }
            }
            
            // Sắp xếp theo dung lượng giảm dần
            usort($files, function($a, $b) {
                return $b['size'] <=> $a['size'];
            });
            
            // Lấy top N
            return array_slice($files, 0, $topN);
        } catch (\Exception $e) {
            Log::warning('🔴 [ToolsController] getTopFiles error', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
        }
        
        return [];
    }
}
