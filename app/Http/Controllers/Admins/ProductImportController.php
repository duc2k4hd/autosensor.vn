<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Image;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProductImportController extends Controller
{
    /**
     * Hiển thị form upload Excel
     */
    public function index()
    {
        return view('admins.products.import');
    }

    /**
     * Upload file và chuẩn bị import (chia batch)
     */
    public function upload(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:10240', // max 10MB
        ]);

        try {
            $file = $request->file('excel_file');
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();

            // Lưu file tạm vào storage
            $sessionId = 'import_' . time() . '_' . Str::random(10);
            $tempPath = storage_path('app/temp_imports/' . $sessionId . '.xlsx');
            File::ensureDirectoryExists(dirname($tempPath));
            $file->move(dirname($tempPath), basename($tempPath));

            // Đọc tất cả dữ liệu và chia batch
            // Tăng batch size để giảm số lần hit DB / IO
            $batchSize = 200; // 200 rows mỗi batch
            $totalRows = $highestRow - 1; // Bỏ header
            $totalBatches = ceil($totalRows / $batchSize);

            // Lưu metadata vào session
            session([
                'import_' . $sessionId => [
                    'file_path' => $tempPath,
                    'total_rows' => $totalRows,
                    'total_batches' => $totalBatches,
                    'batch_size' => $batchSize,
                    'current_batch' => 0,
                ]
            ]);

            return response()->json([
                'success' => true,
                'session_id' => $sessionId,
                'total_rows' => $totalRows,
                'total_batches' => $totalBatches,
            ]);

        } catch (\Exception $e) {
            Log::error('Import upload error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lỗi upload file: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Xử lý import batch
     */
    public function processBatch(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'batch_number' => 'required|integer|min:1',
        ]);

        $sessionId = $request->input('session_id');
        $batchNumber = $request->input('batch_number');
        $sessionKey = 'import_' . $sessionId;

        if (!session()->has($sessionKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Session không tồn tại',
            ], 404);
        }

        $importData = session($sessionKey);
        $filePath = $importData['file_path'];

        if (!File::exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'File không tồn tại',
            ], 404);
        }

        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $batchSize = $importData['batch_size'];
            $startRow = 2 + (($batchNumber - 1) * $batchSize); // Row 2 là header
            $endRow = min($startRow + $batchSize - 1, $sheet->getHighestRow());

            $errors = [];
            $stats = [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
            ];

            // Cache để tối ưu queries
            $brandCache = [];
            $categoryCache = [];
            $tagCache = [];
            $productCache = [];

            DB::beginTransaction();

            // Pre-load brands và categories
            $allBrands = Brand::all()->keyBy(function($brand) {
                return strtolower($brand->name) . '|' . $brand->slug;
            });
            $allCategories = Category::where('is_active', true)->get()->keyBy('slug');

            // Pre-load products theo SKU trong batch để tránh query từng dòng
            $batchSkus = [];
            for ($row = $startRow; $row <= $endRow; $row++) {
                $skuValue = trim($sheet->getCell("A{$row}")->getValue() ?? '');
                if (!empty($skuValue)) {
                    $batchSkus[] = $skuValue;
                }
            }
            $batchSkus = array_values(array_unique($batchSkus));
            if (!empty($batchSkus)) {
                $existingProducts = Product::whereIn('sku', $batchSkus)->get();
                foreach ($existingProducts as $existingProduct) {
                    $productCache[$existingProduct->sku] = $existingProduct;
                }
            }

            for ($row = $startRow; $row <= $endRow; $row++) {
                try {
                    $sku = trim($sheet->getCell("A{$row}")->getValue() ?? '');
                    $name = trim($sheet->getCell("B{$row}")->getValue() ?? '');

                    if (empty($sku) || empty($name)) {
                        $stats['skipped']++;
                        continue;
                    }

                    // Đọc dữ liệu và loại bỏ \n
                    $shortDescription = $this->normalizeText($sheet->getCell("C{$row}")->getValue() ?? '');
                    $shortDescription = $this->cleanHtmlDescription($shortDescription);
                    $description = $this->normalizeText($sheet->getCell("D{$row}")->getValue() ?? '');
                    $description = $this->cleanHtmlDescription($description);
                    $price = $this->parsePrice($sheet->getCell("E{$row}")->getValue() ?? '');
                    $salePrice = $this->parsePrice($sheet->getCell("F{$row}")->getValue() ?? '');
                    $categorySlug = trim($sheet->getCell("G{$row}")->getValue() ?? '');
                    $tagsString = trim($sheet->getCell("H{$row}")->getValue() ?? '');
                    $imageUrls = trim($sheet->getCell("I{$row}")->getValue() ?? '');
                    $brandName = trim($sheet->getCell("J{$row}")->getValue() ?? '');
                    $catalogUrl = trim($sheet->getCell("K{$row}")->getValue() ?? '');

                    // Tìm hoặc tạo product (cached)
                    $product = $productCache[$sku] ?? null;
                    $isUpdate = $product !== null;

                    // Xử lý brand (cached)
                    $brandId = null;
                    if (!empty($brandName)) {
                        $brandKey = strtolower($brandName) . '|' . Str::slug($brandName);
                        if (!isset($brandCache[$brandKey])) {
                            $brand = $allBrands->first(function($b) use ($brandName) {
                                return strtolower($b->name) === strtolower($brandName) 
                                    || $b->slug === Str::slug($brandName);
                            });
                            
                            if (!$brand) {
                                $brand = Brand::create([
                                    'name' => $brandName,
                                    'slug' => Str::slug($brandName),
                                    'is_active' => true,
                                ]);
                                $allBrands->put($brandKey, $brand);
                            }
                            $brandCache[$brandKey] = $brand;
                        }
                        $brandId = $brandCache[$brandKey]->id;
                    }

                    // Xử lý category (cached)
                    $categoryIds = [];
                    $primaryCategoryId = null;
                    if (!empty($categorySlug)) {
                        if (!isset($categoryCache[$categorySlug])) {
                            $category = $allCategories->get($categorySlug);
                            if ($category) {
                                $categoryCache[$categorySlug] = $category;
                            }
                        }
                        if (isset($categoryCache[$categorySlug])) {
                            $category = $categoryCache[$categorySlug];
                            $primaryCategoryId = $category->id;
                            $categoryIds = $this->getCategoryWithParents($category);
                        }
                    }

                    // Xử lý tags (cached và bulk)
                    $tagIds = [];
                    if (!empty($tagsString)) {
                        $tagNames = array_map('trim', explode(',', $tagsString));
                        foreach ($tagNames as $tagName) {
                            if (empty($tagName)) continue;
                            $tagSlug = Str::slug($tagName);
                            
                            if (!isset($tagCache[$tagSlug])) {
                                $tag = Tag::where('entity_type', Product::class)
                                    ->where(function($query) use ($tagName, $tagSlug) {
                                        $query->where('name', $tagName)
                                              ->orWhere('slug', $tagSlug);
                                    })
                                    ->first();
                                
                                if (!$tag) {
                                    $uniqueSlug = $tagSlug;
                                    $counter = 1;
                                    while (Tag::where('slug', $uniqueSlug)->exists()) {
                                        $uniqueSlug = $tagSlug . '-' . $counter;
                                        $counter++;
                                    }
                                    
                                    $tag = Tag::create([
                                        'name' => $tagName,
                                        'slug' => $uniqueSlug,
                                        'entity_type' => Product::class,
                                        'entity_id' => 0,
                                        'is_active' => true,
                                    ]);
                                }
                                $tagCache[$tagSlug] = $tag;
                            }
                            $tagIds[] = $tagCache[$tagSlug]->id;
                        }
                    }

                    // Xử lý hình ảnh (tải về)
                    $imageIds = [];
                    if (!empty($imageUrls)) {
                        $urls = array_map('trim', explode(',', $imageUrls));
                        $urls = array_filter($urls);
                        $urlCount = count($urls);
                        
                        foreach ($urls as $index => $url) {
                            if (empty($url)) continue;
                            try {
                                $imageSku = $urlCount > 1 ? strtoupper($sku) . '-' . ($index + 1) : strtoupper($sku);
                                $imageId = $this->downloadAndSaveImage($url, $imageSku);
                                if ($imageId) {
                                    $imageIds[] = $imageId;
                                }
                            } catch (\Exception $e) {
                                $errors[] = [
                                    'row' => $row,
                                    'sku' => $sku,
                                    'field' => 'image',
                                    'message' => 'Lỗi tải ảnh: ' . $e->getMessage(),
                                ];
                            }
                        }
                    }

                    // Xử lý catalog
                    $linkCatalog = [];
                    if (!empty($catalogUrl)) {
                        try {
                            $catalogPath = $this->downloadAndSaveCatalog($catalogUrl, $name);
                            if ($catalogPath) {
                                $linkCatalog[] = $catalogPath;
                            }
                        } catch (\Exception $e) {
                            $errors[] = [
                                'row' => $row,
                                'sku' => $sku,
                                'field' => 'catalog',
                                'message' => 'Lỗi tải catalog: ' . $e->getMessage(),
                            ];
                        }
                    }

                    // Chuẩn bị dữ liệu product
                    $productData = [
                        'sku' => $sku,
                        'name' => $name,
                        'short_description' => $shortDescription,
                        'description' => $description,
                        'price' => $price,
                        'sale_price' => $salePrice,
                        'brand_id' => $brandId,
                        'primary_category_id' => $primaryCategoryId,
                        'category_ids' => $categoryIds,
                        'tag_ids' => !empty($tagIds) ? $tagIds : null,
                        'is_active' => true,
                    ];

                    if (!empty($imageIds)) {
                        $productData['image_ids'] = $imageIds;
                    }
                    if (!empty($linkCatalog)) {
                        $productData['link_catalog'] = $linkCatalog;
                    }

                    if ($isUpdate) {
                        // Khi update, chỉ cập nhật slug nếu chưa có hoặc khác với SKU
                        $newSlug = $this->normalizeSlug($sku);
                        if (empty($product->slug) || $product->slug !== $newSlug) {
                            $productData['slug'] = $newSlug;
                        }
                        $product->update($productData);
                        $stats['updated']++;
                    } else {
                        // Slug = SKU được normalize (tất cả ký tự đặc biệt thành -)
                        $productData['slug'] = $this->normalizeSlug($sku);
                        $productData['created_by'] = Auth::id();
                        $product = Product::create($productData);
                        $productCache[$sku] = $product; // Cache product mới
                        $stats['created']++;
                    }

                } catch (\Exception $e) {
                    $errors[] = [
                        'row' => $row,
                        'sku' => $sku ?? 'N/A',
                        'message' => $e->getMessage(),
                    ];
                    Log::error('Product import batch error', [
                        'row' => $row,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            // Cập nhật session
            $importData['current_batch'] = $batchNumber;
            session([$sessionKey => $importData]);

            return response()->json([
                'success' => true,
                'batch_number' => $batchNumber,
                'stats' => $stats,
                'errors' => $errors,
                'is_complete' => $batchNumber >= $importData['total_batches'],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Product import batch system error', [
                'batch' => $batchNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lỗi xử lý batch: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Xử lý import Excel (legacy - giữ lại để tương thích)
     */
    public function import(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:10240', // max 10MB
        ]);

        $errors = [];
        $successCount = 0;
        $updateCount = 0;
        $createCount = 0;

        try {
            $file = $request->file('excel_file');
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();

            DB::beginTransaction();

            // Bỏ qua header row (row 1)
            for ($row = 2; $row <= $highestRow; $row++) {
                try {
                    // Đọc dữ liệu từ các cột theo cấu trúc file mẫu:
                    // A=SKU, B=Tên, C=Mô tả ngắn, D=Mô tả, E=Giá gốc, F=Giá khuyến mãi, 
                    // G=Danh mục, H=Thẻ, I=Hình ảnh, J=Thương hiệu, K=Link tài liệu
                    $sku = trim($sheet->getCell("A{$row}")->getValue() ?? '');
                    $name = $this->normalizeText(trim($sheet->getCell("B{$row}")->getValue() ?? ''));
                    $shortDescription = $this->normalizeText($sheet->getCell("C{$row}")->getValue() ?? ''); // Mô tả ngắn (HTML)
                    $description = $this->normalizeText($sheet->getCell("D{$row}")->getValue() ?? ''); // Mô tả chi tiết (HTML)
                    $price = $this->parsePrice($sheet->getCell("E{$row}")->getValue() ?? '');
                    $salePrice = $this->parsePrice($sheet->getCell("F{$row}")->getValue() ?? '');
                    $categorySlug = trim($sheet->getCell("G{$row}")->getValue() ?? '');
                    $tagsString = trim($sheet->getCell("H{$row}")->getValue() ?? ''); // Tags cách nhau dấu phẩy
                    $imageUrls = trim($sheet->getCell("I{$row}")->getValue() ?? ''); // URL hình ảnh
                    $brandName = trim($sheet->getCell("J{$row}")->getValue() ?? '');
                    $catalogUrl = trim($sheet->getCell("K{$row}")->getValue() ?? ''); // Link tài liệu

                    // Bỏ qua nếu thiếu SKU hoặc tên
                    if (empty($sku) || empty($name)) {
                        continue;
                    }

                    // Xử lý mô tả ngắn HTML: loại bỏ class, id, attributes, bỏ img/picture/figcaption
                    $shortDescription = $this->cleanHtmlDescription($shortDescription);
                    
                    // Xử lý mô tả HTML: loại bỏ class, id, attributes, bỏ img/picture/figcaption
                    $description = $this->cleanHtmlDescription($description);

                    // Tìm hoặc tạo product theo SKU
                    $product = Product::where('sku', $sku)->first();
                    $isUpdate = $product !== null;

                    // Xử lý thương hiệu
                    $brandId = null;
                    if (!empty($brandName)) {
                        $brand = Brand::where('name', $brandName)
                            ->orWhere('slug', Str::slug($brandName))
                            ->first();
                        if (!$brand) {
                            // Tạo brand mới nếu chưa có
                            $brand = Brand::create([
                                'name' => $brandName,
                                'slug' => Str::slug($brandName),
                                'is_active' => true,
                            ]);
                        }
                        $brandId = $brand->id;
                    }

                    // Xử lý danh mục (slug con, tự động thêm danh mục cha)
                    $categoryIds = [];
                    $primaryCategoryId = null;
                    if (!empty($categorySlug)) {
                        $category = Category::where('slug', $categorySlug)->where('is_active', true)->first();
                        if ($category) {
                            $primaryCategoryId = $category->id;
                            $categoryIds = $this->getCategoryWithParents($category);
                        }
                    }

                    // Xử lý hình ảnh: tải về và đặt tên SKU.đuôi (nếu nhiều ảnh thì thêm số thứ tự)
                    $imageIds = [];
                    if (!empty($imageUrls)) {
                        $urls = array_map('trim', explode(',', $imageUrls));
                        $urls = array_filter($urls); // Loại bỏ empty
                        $urlCount = count($urls);
                        
                        foreach ($urls as $index => $url) {
                            if (empty($url)) continue;
                            try {
                                // Nếu có nhiều ảnh, thêm số thứ tự vào tên file
                                $imageSku = $urlCount > 1 ? strtoupper($sku) . '-' . ($index + 1) : strtoupper($sku);
                                $imageId = $this->downloadAndSaveImage($url, $imageSku, $name);
                                if ($imageId) {
                                    $imageIds[] = $imageId;
                                }
                            } catch (\Exception $e) {
                                $errors[] = [
                                    'row' => $row,
                                    'sku' => $sku,
                                    'field' => 'image',
                                    'url' => $url,
                                    'message' => 'Lỗi tải ảnh: ' . $e->getMessage(),
                                ];
                            }
                        }
                    }

                    // Xử lý tags: tách chuỗi và tìm/tạo tags
                    $tagIds = [];
                    if (!empty($tagsString)) {
                        $tagNames = array_map('trim', explode(',', $tagsString));
                        foreach ($tagNames as $tagName) {
                            if (empty($tagName)) continue;
                            
                            // Tìm tag theo name hoặc slug (entity_type = Product)
                            $tag = Tag::where('entity_type', Product::class)
                                ->where(function($query) use ($tagName) {
                                    $query->where('name', $tagName)
                                          ->orWhere('slug', Str::slug($tagName));
                                })
                                ->first();
                            
                            if (!$tag) {
                                // Tạo tag mới với slug unique
                                $baseSlug = Str::slug($tagName);
                                $uniqueSlug = $baseSlug;
                                $counter = 1;
                                while (Tag::where('slug', $uniqueSlug)->exists()) {
                                    $uniqueSlug = $baseSlug . '-' . $counter;
                                    $counter++;
                                }
                                
                                $tag = Tag::create([
                                    'name' => $tagName,
                                    'slug' => $uniqueSlug,
                                    'entity_type' => Product::class,
                                    'entity_id' => 0, // Tag template, không gắn với product cụ thể (giống ImportExcelController)
                                    'is_active' => true,
                                ]);
                            }
                            $tagIds[] = $tag->id;
                        }
                    }

                    // Xử lý link catalog: tải về và đặt vào public/clients/assets/catalog
                    $linkCatalog = [];
                    if (!empty($catalogUrl)) {
                        try {
                            $catalogPath = $this->downloadAndSaveCatalog($catalogUrl, $name);
                            if ($catalogPath) {
                                $linkCatalog[] = $catalogPath;
                            }
                        } catch (\Exception $e) {
                            $errors[] = [
                                'row' => $row,
                                'sku' => $sku,
                                'field' => 'catalog',
                                'url' => $catalogUrl,
                                'message' => 'Lỗi tải catalog: ' . $e->getMessage(),
                            ];
                        }
                    }

                    // Chuẩn bị dữ liệu product
                    $productData = [
                        'sku' => $sku,
                        'name' => $name,
                        'short_description' => $shortDescription,
                        'description' => $description,
                        'price' => $price,
                        'sale_price' => $salePrice,
                        'brand_id' => $brandId,
                        'primary_category_id' => $primaryCategoryId,
                        'category_ids' => $categoryIds,
                        'tag_ids' => !empty($tagIds) ? $tagIds : null,
                        'is_active' => true,
                    ];

                    // Chỉ cập nhật image_ids và link_catalog nếu có dữ liệu mới
                    if (!empty($imageIds)) {
                        $productData['image_ids'] = $imageIds;
                    }
                    if (!empty($linkCatalog)) {
                        $productData['link_catalog'] = $linkCatalog;
                    }

                    if ($isUpdate) {
                        // Khi update, chỉ cập nhật slug nếu chưa có hoặc khác với SKU
                        $newSlug = $this->normalizeSlug($sku);
                        if (empty($product->slug) || $product->slug !== $newSlug) {
                            $productData['slug'] = $newSlug;
                        }
                        $product->update($productData);
                        $updateCount++;
                    } else {
                        // Slug = SKU được normalize (tất cả ký tự đặc biệt thành -)
                        $productData['slug'] = $this->normalizeSlug($sku);
                        $productData['created_by'] = Auth::id();
                        $product = Product::create($productData);
                        $createCount++;
                    }

                    $successCount++;

                } catch (\Exception $e) {
                    $errors[] = [
                        'row' => $row,
                        'sku' => $sku ?? 'N/A',
                        'message' => $e->getMessage(),
                    ];
                    Log::error('Product import error', [
                        'row' => $row,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            DB::commit();

            $message = "Import thành công! Tạo mới: {$createCount}, Cập nhật: {$updateCount}";
            if (!empty($errors)) {
                $message .= ". Có " . count($errors) . " lỗi.";
            }

            return redirect()->back()
                ->with('success', $message)
                ->with('import_errors', $errors);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Product import system error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'Lỗi import: ' . $e->getMessage());
        }
    }

    /**
     * Làm sạch HTML mô tả: loại bỏ class, id, attributes, bỏ img/picture/figcaption
     * Decode HTML entities và loại bỏ các thẻ rỗng
     */
    private function cleanHtmlDescription($html)
    {
        if (empty($html)) {
            return '';
        }

        // Chuyển đổi HTML string sang DOMDocument
        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);

        // QUAN TRỌNG: chuyển UTF-8 sang HTML-ENTITIES để DOMDocument hiểu đúng tiếng Việt
        // Nếu bỏ bước này sẽ dễ bị vỡ font kiểu "ThÃ´ng sá»..."
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');

        // Load HTML với encoding UTF-8, không thêm XML declaration
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        // Xóa các thẻ img, picture, figcaption
        $nodesToRemove = $xpath->query('//img | //picture | //figcaption');
        foreach ($nodesToRemove as $node) {
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
            }
        }

        // Loại bỏ các thẻ rỗng (p, div, span, etc. không có nội dung hoặc chỉ có whitespace)
        $emptyTags = $xpath->query('//p[not(node()) or not(normalize-space())] | //div[not(node()) or not(normalize-space())] | //span[not(node()) or not(normalize-space())]');
        foreach ($emptyTags as $node) {
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
            }
        }

        // Loại bỏ tất cả attributes từ tất cả các thẻ (giữ lại tên thẻ và nội dung)
        $allNodes = $xpath->query('//*');
        foreach ($allNodes as $node) {
            // Chỉ xóa attributes nếu node là DOMElement
            if ($node instanceof \DOMElement && $node->attributes) {
                // Tạo array của attribute names để tránh lỗi khi xóa trong loop
                $attributesToRemove = [];
                foreach ($node->attributes as $attr) {
                    $attributesToRemove[] = $attr->nodeName;
                }
                foreach ($attributesToRemove as $attrName) {
                    $node->removeAttribute($attrName);
                }
            }
        }

        // Lấy body content (vì DOMDocument tự động wrap trong html/body)
        $body = $dom->getElementsByTagName('body')->item(0);
        if ($body) {
            $cleanedHtml = '';
            foreach ($body->childNodes as $child) {
                $cleanedHtml .= $dom->saveHTML($child);
            }
        } else {
            $cleanedHtml = $dom->saveHTML();
        }
        
        // Loại bỏ XML encoding declaration và các thẻ wrapper không cần thiết
        $cleanedHtml = preg_replace('/<\?xml[^>]*\?>/i', '', $cleanedHtml);
        $cleanedHtml = preg_replace('/<!DOCTYPE[^>]*>/i', '', $cleanedHtml);
        $cleanedHtml = preg_replace('/<html[^>]*>/i', '', $cleanedHtml);
        $cleanedHtml = preg_replace('/<\/html>/i', '', $cleanedHtml);
        $cleanedHtml = preg_replace('/<body[^>]*>/i', '', $cleanedHtml);
        $cleanedHtml = preg_replace('/<\/body>/i', '', $cleanedHtml);
        
        // Loại bỏ các thẻ rỗng còn sót lại (p, div, span rỗng) - lặp lại cho đến khi không còn
        $previousHtml = '';
        while ($previousHtml !== $cleanedHtml) {
            $previousHtml = $cleanedHtml;
            $cleanedHtml = preg_replace('/<p>\s*<\/p>/i', '', $cleanedHtml);
            $cleanedHtml = preg_replace('/<div>\s*<\/div>/i', '', $cleanedHtml);
            $cleanedHtml = preg_replace('/<span>\s*<\/span>/i', '', $cleanedHtml);
            $cleanedHtml = preg_replace('/<p><\/p>/i', '', $cleanedHtml);
            $cleanedHtml = preg_replace('/<div><\/div>/i', '', $cleanedHtml);
            $cleanedHtml = preg_replace('/<span><\/span>/i', '', $cleanedHtml);
            $cleanedHtml = preg_replace('/<td>\s*<\/td>/i', '', $cleanedHtml);
            $cleanedHtml = preg_replace('/<th>\s*<\/th>/i', '', $cleanedHtml);
        }
        
        // Chuẩn hóa khoảng trắng (nhiều space thành 1 space)
        $cleanedHtml = preg_replace('/\s+/', ' ', $cleanedHtml);
        
        // Trim đầu cuối
        return trim($cleanedHtml);
    }

    /**
     * Tải và lưu hình ảnh từ URL
     */
    private function downloadAndSaveImage($url, $sku, $productName = null)
    {
        try {
            // Lấy extension từ URL
            $urlPath = parse_url($url, PHP_URL_PATH);
            $extension = strtolower(pathinfo($urlPath, PATHINFO_EXTENSION));
            if (empty($extension)) {
                $extension = 'jpg'; // Default
            }

            // Chuẩn hóa SKU cho tên file: thay mọi ký tự không phải chữ/số thành dấu gạch
            $normalizedSku = preg_replace('/[^A-Za-z0-9]/', '-', $sku);
            $normalizedSku = preg_replace('/-+/', '-', $normalizedSku);
            $normalizedSku = trim($normalizedSku, '-');
            $normalizedSku = strtoupper($normalizedSku);

            // Tên file: SKU.đuôi
            $fileName = $normalizedSku . '.' . $extension;
            $filePath = public_path('clients/assets/img/clothes/' . $fileName);

            // Nếu file và record đã tồn tại, tái sử dụng để tránh nhân bản
            $existing = Image::where('url', $fileName)->first();
            if ($existing && File::exists($filePath)) {
                return $existing->id;
            }

            // Tải ảnh
            $response = Http::timeout(30)->get($url);
            if ($response->successful()) {
                // Lưu file
                File::ensureDirectoryExists(public_path('clients/assets/img/clothes'));
                File::put($filePath, $response->body());

                // Tạo record Image trong database (hoặc cập nhật alt/title nếu đã có record nhưng chưa có file)
                $altTitle = trim((string) $productName) !== '' ? trim($productName) : $sku;
                $payload = [
                    'url' => $fileName,
                    'alt' => $altTitle,
                    'title' => $altTitle,
                ];

                $sizes = $this->getResizeSizes();

                if ($existing) {
                    $existing->update($payload);
                    // Resize lại (nếu cần) sau khi đảm bảo file tồn tại
                    $this->generateResizedImagesForSingle($fileName, $sizes);
                    return $existing->id;
                }

                $image = Image::create($payload);
                // Tạo bản resize cho ảnh mới
                $this->generateResizedImagesForSingle($fileName, $sizes);
                return $image->id;
            }
        } catch (\Exception $e) {
            Log::error('Error downloading image', [
                'url' => $url,
                'sku' => $sku,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Resize ảnh theo các kích thước yêu cầu, tương tự ProductService
     *
     * @param string $relativePath tên file ảnh (ví dụ: ABC-1.WEBP)
     * @param array<array{0:int,1:int}> $sizes danh sách [w,h]
     */
    private function generateResizedImagesForSingle(string $relativePath, array $sizes): void
    {
        if ($relativePath === '') {
            return;
        }

        $originalPath = public_path('clients/assets/img/clothes/'.$relativePath);
        if (! is_file($originalPath)) {
            return;
        }

        $resizeRoot = public_path('clients/assets/img/clothes/resize');
        File::ensureDirectoryExists($resizeRoot);

        $extension = pathinfo($originalPath, PATHINFO_EXTENSION) ?: 'webp';
        $baseName = pathinfo($originalPath, PATHINFO_FILENAME);

        foreach ($sizes as $size) {
            [$width, $height] = $size;
            if (! $width || ! $height) {
                continue;
            }

            $sizeFolder = $width.'x'.$height;
            $resizeDir = $resizeRoot.DIRECTORY_SEPARATOR.$sizeFolder;
            File::ensureDirectoryExists($resizeDir);

            $targetFilename = $baseName.'.'.$extension;
            $targetPath = $resizeDir.DIRECTORY_SEPARATOR.$targetFilename;

            try {
                $this->resizeWithGd($originalPath, $targetPath, $width, $height, $extension);
            } catch (\Throwable $e) {
                Log::error('generateResizedImagesForSingle (import) failed', [
                    'source' => $relativePath,
                    'width' => $width,
                    'height' => $height,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Danh sách kích thước resize (bổ sung các size đang được dùng trong hệ thống)
     */
    private function getResizeSizes(): array
    {
        return [
            [500, 500],
            [300, 300],
            [150, 150]
        ];
    }

    /**
     * Resize ảnh bằng GD để tránh phụ thuộc thư viện ngoài
     */
    private function resizeWithGd(string $sourcePath, string $targetPath, int $width, int $height, string $extension): void
    {
        // Đọc ảnh nguồn
        $createFn = match (strtolower($extension)) {
            'png' => 'imagecreatefrompng',
            'webp' => 'imagecreatefromwebp',
            default => 'imagecreatefromjpeg',
        };

        if (!function_exists($createFn)) {
            return;
        }

        $src = @$createFn($sourcePath);
        if (!$src) {
            return;
        }

        $srcW = imagesx($src);
        $srcH = imagesy($src);
        if ($srcW <= 0 || $srcH <= 0) {
            imagedestroy($src);
            return;
        }

        // Tính scale cover để đủ kích thước và crop giữa
        $scale = max($width / $srcW, $height / $srcH);
        $tmpW = (int) ceil($srcW * $scale);
        $tmpH = (int) ceil($srcH * $scale);

        $tmp = imagecreatetruecolor($tmpW, $tmpH);

        // Preserve alpha cho PNG/WebP
        if (in_array(strtolower($extension), ['png', 'webp'])) {
            imagealphablending($tmp, false);
            imagesavealpha($tmp, true);
            $transparent = imagecolorallocatealpha($tmp, 0, 0, 0, 127);
            imagefill($tmp, 0, 0, $transparent);
        }

        imagecopyresampled($tmp, $src, 0, 0, 0, 0, $tmpW, $tmpH, $srcW, $srcH);
        imagedestroy($src);

        // Crop giữa để đúng tỷ lệ
        $dst = imagecreatetruecolor($width, $height);
        if (in_array(strtolower($extension), ['png', 'webp'])) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefill($dst, 0, 0, $transparent);
        }

        $x = (int) max(0, ($tmpW - $width) / 2);
        $y = (int) max(0, ($tmpH - $height) / 2);
        imagecopy($dst, $tmp, 0, 0, $x, $y, $width, $height);
        imagedestroy($tmp);

        // Lưu file
        File::ensureDirectoryExists(dirname($targetPath));

        switch (strtolower($extension)) {
            case 'png':
                // 0 (no compression) - 9 (max). Dùng 6 để cân bằng
                imagepng($dst, $targetPath, 6);
                break;
            case 'webp':
                imagewebp($dst, $targetPath, 90);
                break;
            default:
                imagejpeg($dst, $targetPath, 92);
                break;
        }

        imagedestroy($dst);
    }

    /**
     * Tải và lưu catalog từ URL
     */
    private function downloadAndSaveCatalog($url, $productName)
    {
        try {
            // Tạo slug từ tên sản phẩm
            $slug = Str::slug($productName);

            // Lấy extension từ URL
            $urlPath = parse_url($url, PHP_URL_PATH);
            $extension = strtolower(pathinfo($urlPath, PATHINFO_EXTENSION));
            if (empty($extension)) {
                $extension = 'pdf'; // Default
            }

            // Tên file: slug.đuôi
            $fileName = $slug . '.' . $extension;
            $filePath = public_path('clients/assets/catalog/' . $fileName);

            // Tải file
            $response = Http::timeout(60)->get($url);
            if ($response->successful()) {
                // Lưu file
                File::ensureDirectoryExists(public_path('clients/assets/catalog'));
                File::put($filePath, $response->body());

                // Trả về relative path
                return 'clients/assets/catalog/' . $fileName;
            }
        } catch (\Exception $e) {
            Log::error('Error downloading catalog', [
                'url' => $url,
                'product_name' => $productName,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Lấy danh mục và tất cả danh mục cha
     */
    private function getCategoryWithParents(Category $category)
    {
        $categoryIds = [$category->id];
        $current = $category;

        // Đi lên cây danh mục để lấy tất cả danh mục cha
        while ($current->parent_id) {
            $parent = Category::find($current->parent_id);
            if ($parent && $parent->is_active) {
                $categoryIds[] = $parent->id;
                $current = $parent;
            } else {
                break;
            }
        }

        return array_unique($categoryIds);
    }

    /**
     * Parse giá từ cell Excel (có thể là số hoặc string)
     */
    private function parsePrice($value)
    {
        if (empty($value)) {
            return null;
        }
        
        // Nếu là số, trả về trực tiếp
        if (is_numeric($value)) {
            return (float) $value;
        }
        
        // Nếu là string, loại bỏ ký tự không phải số và dấu chấm/phẩy
        $cleaned = preg_replace('/[^\d.,]/', '', $value);
        $cleaned = str_replace(',', '', $cleaned);
        
        return !empty($cleaned) ? (float) $cleaned : null;
    }

    /**
     * Normalize slug: chuyển tất cả ký tự đặc biệt thành dấu gạch ngang
     * Ví dụ: "S8VK-C24024", "E3T FD11 2M", "ABC*XYZ/123" -> "s8vk-c24024", "e3t-fd11-2m", "abc-xyz-123"
     */
    private function normalizeSlug($text)
    {
        // Chuyển sang chữ thường
        $slug = strtolower(trim($text));
        
        // Thay thế tất cả ký tự không phải chữ, số, hoặc dấu gạch ngang thành dấu gạch ngang
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        
        // Loại bỏ các dấu gạch ngang liên tiếp (--- thành -)
        $slug = preg_replace('/-+/', '-', $slug);
        
        // Loại bỏ dấu gạch ngang ở đầu và cuối
        $slug = trim($slug, '-');
        
        return $slug;
    }

    /**
     * Normalize text: loại bỏ tất cả ký tự xuống dòng (\n, \r\n, \r)
     * và chuẩn hóa khoảng trắng, loại bỏ <br />\n trong HTML
     */
    private function normalizeText($text)
    {
        if (empty($text)) {
            return '';
        }
        
        // Loại các chuỗi xuống dòng kiểu Excel (_x000D_) hoặc escape literal \n, \r
        $text = str_replace(['_x000D_', '\\r', '\\n'], '', $text);

        // Loại bỏ <br />\n (với backslash literal trong string) - xuất hiện trong HTML string
        // Pattern: <br />\n hoặc <br/>\n hoặc <br>\n (với backslash literal \\n)
        $text = preg_replace('/<br\s*\/?>\s*\\\\n/i', '', $text);
        
        // Loại bỏ <br />\n (với newline thực sự) - các biến thể
        $text = preg_replace('/<br\s*\/?>\s*\r\n/i', '', $text);
        $text = preg_replace('/<br\s*\/?>\s*\n/i', '', $text);
        $text = preg_replace('/<br\s*\/?>\s*\r/i', '', $text);
        
        // Loại bỏ tất cả ký tự xuống dòng còn lại (\r\n, \r, \n)
        $text = str_replace(["\r\n", "\r", "\n"], '', $text);
        
        // Loại bỏ <br /> đơn lẻ không cần thiết (ở đầu/cuối hoặc liên tiếp)
        $text = preg_replace('/^\s*<br\s*\/?>\s*/i', '', $text);
        $text = preg_replace('/\s*<br\s*\/?>\s*$/i', '', $text);
        // Loại bỏ nhiều <br /> liên tiếp (lặp lại cho đến khi không còn)
        while (preg_match('/(<br\s*\/?>\s*){2,}/i', $text)) {
            $text = preg_replace('/(<br\s*\/?>\s*){2,}/i', '', $text);
        }
        
        // Chuẩn hóa khoảng trắng: nhiều space thành 1 space
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Trim đầu cuối
        return trim($text);
    }
}

