<?php

namespace App\Jobs;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Cell;

class ExportProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $sessionId;
    protected $categoryIds;
    protected $brandIds;
    protected $totalProducts;

    /**
     * Create a new job instance.
     */
    public function __construct(string $sessionId, array $categoryIds, array $brandIds, int $totalProducts)
    {
        $this->sessionId = $sessionId;
        $this->categoryIds = $categoryIds;
        $this->brandIds = $brandIds;
        $this->totalProducts = $totalProducts;
    }

    /**
     * Execute the job.
     * Sử dụng OpenSpout để export XLSX streaming thật - KHÔNG OOM
     */
    public function handle()
    {
        set_time_limit(0);
        // OpenSpout không cần memory limit cao vì streaming thật - chỉ cần 128M là đủ
        ini_set('memory_limit', '128M');
        
        Log::info('Export job started (OpenSpout)', [
            'session_id' => $this->sessionId,
            'total_products' => $this->totalProducts,
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
        ]);

        $cacheKey = "export_{$this->sessionId}";
        $exportData = Cache::get($cacheKey);

        if (!$exportData) {
            Log::error('Export job: Session not found', ['session_id' => $this->sessionId]);
            return;
        }

        try {
            // Cập nhật status
            Cache::put($cacheKey, array_merge($exportData, [
                'status' => 'processing',
            ]), now()->addHours(2));

            $exportDir = storage_path('app/exports');
            if (!is_dir($exportDir)) {
                mkdir($exportDir, 0755, true);
            }

            $filePath = "{$exportDir}/{$this->sessionId}.xlsx";

            // Load maps (nhỏ, không ảnh hưởng RAM)
            $categoryMap = Category::pluck('slug', 'id')->toArray();
            $brandMap = Brand::pluck('slug', 'id')->toArray();
            $tagMap = Tag::pluck('name', 'id')->toArray();

            // Tạo writer với OpenSpout - STREAMING THẬT
            $options = new Options();
            $writer = new Writer($options);
            $writer->openToFile($filePath);

            // Headers
            $headers = [
                'sku', 'name', 'slug', 'description', 'short_description',
                'price', 'sale_price', 'cost_price', 'stock_quantity',
                'meta_title', 'meta_description', 'meta_keywords',
                'meta_canonical', 'primary_category_slug', 'brand_slug',
                'category_slugs', 'tag_slugs',
                'image_ids', 'link_catalog', 'is_featured', 'is_active',
            ];
            
            // Tạo Row từ array of values
            $headerCells = array_map(fn($value) => Cell::fromValue($value), $headers);
            $headerRow = new Row($headerCells);
            $writer->addRow($headerRow);

            $processed = 0;
            $chunkSize = 200; // Có thể tăng vì OpenSpout streaming thật

            // Build query
            $query = \App\Models\Product::query();
            
            if (!empty($this->categoryIds)) {
                $query->where(function ($q) {
                    $q->whereIn('primary_category_id', $this->categoryIds);
                    foreach ($this->categoryIds as $catId) {
                        $q->orWhereJsonContains('category_ids', $catId);
                    }
                });
            }

            if (!empty($this->brandIds)) {
                $query->whereIn('brand_id', $this->brandIds);
            }

            $query->select([
                'id', 'sku', 'name', 'slug', 'description', 'short_description',
                'price', 'sale_price', 'cost_price', 'stock_quantity',
                'meta_title', 'meta_description', 'meta_keywords',
                'meta_canonical', 'primary_category_id', 'brand_id',
                'category_ids', 'tag_ids', 'image_ids', 'link_catalog',
                'is_featured', 'is_active',
            ])->orderBy('id');

            // Process chunks - OpenSpout ghi từng dòng, không giữ trong RAM
            $query->chunkById($chunkSize, function ($products) use (&$processed, $writer, $categoryMap, $brandMap, $tagMap, $cacheKey) {
                foreach ($products as $p) {
                    $primarySlug = $p->primary_category_id ? ($categoryMap[$p->primary_category_id] ?? null) : null;
                    $brandSlug = $p->brand_id ? ($brandMap[$p->brand_id] ?? null) : null;

                    $categorySlugs = '';
                    if (!empty($p->category_ids) && is_array($p->category_ids)) {
                        $slugs = array_map(function ($id) use ($categoryMap) {
                            return $categoryMap[$id] ?? null;
                        }, $p->category_ids);
                        $categorySlugs = implode(',', array_filter($slugs));
                    }

                    $tagNames = '';
                    if (!empty($p->tag_ids) && is_array($p->tag_ids)) {
                        $names = array_map(function ($id) use ($tagMap) {
                            return $tagMap[$id] ?? null;
                        }, $p->tag_ids);
                        $tagNames = implode(',', array_filter($names));
                    }

                    $imageIds = '';
                    if (!empty($p->image_ids) && is_array($p->image_ids)) {
                        $imageIds = implode(',', array_map(fn ($id) => 'IMG'.$id, $p->image_ids));
                    }

                    $linkCatalog = '';
                    if (!empty($p->link_catalog) && is_array($p->link_catalog)) {
                        $linkCatalog = implode(',', $p->link_catalog);
                    } elseif (is_string($p->link_catalog)) {
                        $linkCatalog = $p->link_catalog;
                    }

                    $metaKeywords = is_array($p->meta_keywords) ? implode(',', $p->meta_keywords) : ($p->meta_keywords ?? '');

                    // Ghi row - OpenSpout ghi trực tiếp vào file, không giữ trong RAM
                    $rowValues = [
                        $p->sku,
                        $p->name,
                        $p->slug,
                        $p->description,
                        $p->short_description,
                        $p->price,
                        $p->sale_price,
                        $p->cost_price,
                        $p->stock_quantity,
                        $p->meta_title,
                        $p->meta_description,
                        $metaKeywords,
                        $p->meta_canonical,
                        $primarySlug,
                        $brandSlug,
                        $categorySlugs,
                        $tagNames,
                        $imageIds,
                        $linkCatalog,
                        $p->is_featured ? 1 : 0,
                        $p->is_active ? 1 : 0,
                    ];
                    
                    // Tạo Row từ array of values
                    $rowCells = array_map(fn($value) => Cell::fromValue($value), $rowValues);
                    $row = new Row($rowCells);
                    $writer->addRow($row);

                    $processed++;
                }

                // Update progress
                $progress = $this->totalProducts > 0 ? ($processed / $this->totalProducts) * 100 : 0;
                Cache::put($cacheKey, array_merge(Cache::get($cacheKey, []), [
                    'processed' => $processed,
                    'progress' => round($progress, 2),
                ]), now()->addHours(2));

                // Cleanup sau mỗi chunk
                unset($products);
                gc_collect_cycles();
                
                // Log memory usage mỗi 1000 products
                if ($processed % 1000 === 0) {
                    $memoryUsage = memory_get_usage(true);
                    $memoryPeak = memory_get_peak_usage(true);
                    Log::info('Export progress (OpenSpout)', [
                        'session_id' => $this->sessionId,
                        'processed' => $processed,
                        'total' => $this->totalProducts,
                        'memory_usage_mb' => round($memoryUsage / 1024 / 1024, 2),
                        'memory_peak_mb' => round($memoryPeak / 1024 / 1024, 2),
                    ]);
                }
            });

            // Close writer - file đã được ghi xong
            $writer->close();

            Log::info('Saving export file (OpenSpout)', [
                'session_id' => $this->sessionId,
                'file_path' => $filePath,
                'total_rows' => $processed,
            ]);

            // Kiểm tra file đã được tạo chưa
            if (!file_exists($filePath)) {
                throw new \Exception('File export chưa được tạo sau khi save.');
            }

            $fileSize = filesize($filePath);
            if ($fileSize === false || $fileSize === 0) {
                throw new \Exception('File export rỗng hoặc không hợp lệ.');
            }

            Log::info('Export file saved (OpenSpout)', [
                'session_id' => $this->sessionId,
                'file_path' => $filePath,
                'file_size_mb' => round($fileSize / 1024 / 1024, 2),
                'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            ]);

            // OpenSpout tự cleanup temp files khi close()

            // Cập nhật status completed
            Cache::put($cacheKey, array_merge($exportData, [
                'status' => 'completed',
                'processed' => $processed,
                'file_path' => $filePath,
                'completed_at' => now()->toDateTimeString(),
            ]), now()->addHours(2));

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $isMemoryError = strpos($errorMessage, 'memory') !== false || 
                            strpos($errorMessage, 'Memory') !== false ||
                            strpos($errorMessage, 'exhausted') !== false;
            
            Log::error('Export job error (OpenSpout)', [
                'session_id' => $this->sessionId,
                'error' => $errorMessage,
                'is_memory_error' => $isMemoryError,
                'memory_limit' => ini_get('memory_limit'),
                'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'trace' => $e->getTraceAsString(),
            ]);

            Cache::put($cacheKey, array_merge($exportData, [
                'status' => 'error',
                'error' => $errorMessage,
            ]), now()->addHours(2));
            
            // OpenSpout tự cleanup temp files
        }
    }
}
