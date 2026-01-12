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
use Illuminate\View\View;

class ToolsController extends Controller
{
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
                
                // Cập nhật batch cho chunk này
                if (!empty($tagUsageCounts)) {
                    foreach ($tagUsageCounts as $tagId => $count) {
                        DB::table('tags')
                            ->where('id', $tagId)
                            ->increment('usage_count', $count);
                    }
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
                
                // Cập nhật batch cho chunk này
                if (!empty($tagUsageCounts)) {
                    foreach ($tagUsageCounts as $tagId => $count) {
                        DB::table('tags')
                            ->where('id', $tagId)
                            ->increment('usage_count', $count);
                    }
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
     */
    public function getUnusedImagesStats(): JsonResponse
    {
        try {
            Log::info('🔵 [DEBUG ToolsController] getUnusedImagesStats - Starting');
            
            $clothesPath = public_path('clients/assets/img/clothes');
            
            if (!File::exists($clothesPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thư mục không tồn tại: ' . $clothesPath,
                ], 404);
            }
            
            // Lấy tất cả file ảnh trong thư mục (bao gồm cả thư mục con)
            $allFiles = File::allFiles($clothesPath);
            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
            
            $unusedImages = [];
            $totalSize = 0;
            $checkedCount = 0;
            $usedCount = 0;
            
            foreach ($allFiles as $file) {
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
                
                // Bỏ qua file no-image.webp (file mặc định)
                if (strtolower($fileName) === 'no-image.webp') {
                    continue;
                }
                
                $checkedCount++;
                
                // Kiểm tra trong database
                $isUsed = false;
                
                // 1. Kiểm tra trong bảng images (cột: url)
                if (Image::where('url', 'LIKE', '%' . $fileName . '%')->exists()) {
                    $isUsed = true;
                }
                
                // 2. Kiểm tra trong bảng banners (cột: image_desktop, image_mobile)
                if (!$isUsed && Banner::where(function($query) use ($fileName) {
                    $query->where('image_desktop', 'LIKE', '%' . $fileName . '%')
                          ->orWhere('image_mobile', 'LIKE', '%' . $fileName . '%');
                })->exists()) {
                    $isUsed = true;
                }
                
                // 3. Kiểm tra trong bảng products (description, short_description)
                if (!$isUsed && Product::where(function($query) use ($fileName) {
                    $query->where('description', 'LIKE', '%' . $fileName . '%')
                          ->orWhere('short_description', 'LIKE', '%' . $fileName . '%');
                })->exists()) {
                    $isUsed = true;
                }
                
                // 4. Kiểm tra trong bảng posts (content)
                if (!$isUsed && Post::where('content', 'LIKE', '%' . $fileName . '%')->exists()) {
                    $isUsed = true;
                }
                
                if ($isUsed) {
                    $usedCount++;
                    continue;
                }
                
                // Ảnh không được sử dụng
                $fileSize = $file->getSize();
                $totalSize += $fileSize;
                
                $unusedImages[] = [
                    'path' => $relativePath,
                    'name' => $fileName,
                    'size' => $this->formatBytes($fileSize),
                    'size_bytes' => $fileSize,
                    'url' => asset($relativePath),
                ];
                
                // Giới hạn 100 ảnh để hiển thị, tránh load quá nhiều
                if (count($unusedImages) >= 100) {
                    break;
                }
            }
            
            Log::info('🟢 [DEBUG ToolsController] Image scan completed', [
                'checked' => $checkedCount,
                'used' => $usedCount,
                'unused' => count($unusedImages),
                'total_size' => $this->formatBytes($totalSize),
            ]);
            
            return response()->json([
                'success' => true,
                'unused_count' => count($unusedImages),
                'unused_images' => $unusedImages,
                'total_size' => $this->formatBytes($totalSize),
                'total_size_bytes' => $totalSize,
                'checked_count' => $checkedCount,
                'used_count' => $usedCount,
                'has_more' => $checkedCount > 100,
            ]);
        } catch (\Exception $e) {
            Log::error('🔴 [DEBUG ToolsController] Exception in getUnusedImagesStats', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Xóa ảnh sản phẩm không được sử dụng
     */
    public function deleteUnusedImages(Request $request): JsonResponse
    {
        try {
            Log::info('🔵 [DEBUG ToolsController] deleteUnusedImages - Starting');
            
            $clothesPath = public_path('clients/assets/img/clothes');
            
            if (!File::exists($clothesPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thư mục không tồn tại: ' . $clothesPath,
                ], 404);
            }
            
            // Lấy tất cả file ảnh trong thư mục
            $allFiles = File::allFiles($clothesPath);
            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
            
            $deletedImages = [];
            $deletedSize = 0;
            $deletedCount = 0;
            
            foreach ($allFiles as $file) {
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
                
                // Bỏ qua file no-image.webp (file mặc định)
                if (strtolower($fileName) === 'no-image.webp') {
                    continue;
                }
                
                // Kiểm tra trong database
                $isUsed = false;
                
                // 1. Kiểm tra trong bảng images (cột: url)
                if (Image::where('url', 'LIKE', '%' . $fileName . '%')->exists()) {
                    $isUsed = true;
                }
                
                // 2. Kiểm tra trong bảng banners (cột: image_desktop, image_mobile)
                if (!$isUsed && Banner::where(function($query) use ($fileName) {
                    $query->where('image_desktop', 'LIKE', '%' . $fileName . '%')
                          ->orWhere('image_mobile', 'LIKE', '%' . $fileName . '%');
                })->exists()) {
                    $isUsed = true;
                }
                
                // 3. Kiểm tra trong bảng products (description, short_description)
                if (!$isUsed && Product::where(function($query) use ($fileName) {
                    $query->where('description', 'LIKE', '%' . $fileName . '%')
                          ->orWhere('short_description', 'LIKE', '%' . $fileName . '%');
                })->exists()) {
                    $isUsed = true;
                }
                
                // 4. Kiểm tra trong bảng posts (content)
                if (!$isUsed && Post::where('content', 'LIKE', '%' . $fileName . '%')->exists()) {
                    $isUsed = true;
                }
                
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
            ]);
        } catch (\Exception $e) {
            Log::error('🔴 [DEBUG ToolsController] Exception in deleteUnusedImages', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
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
}
