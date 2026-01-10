<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Image;
use App\Models\Product;
use App\Models\ProductFaq;
use App\Models\ProductHowTo;
use App\Models\ProductVariant;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ImportExcelController extends Controller
{
    /**
     * Hiển thị form upload Excel
     */
    public function index()
    {
        return view('admins.products.import-excel');
    }

    /**
     * Export toàn bộ sản phẩm ra file Excel
     * File Excel gồm 5 sheets: products, images, faqs, how_tos, variants
     * Products sheet bao gồm: sku, name, slug, description, short_description, price, sale_price, cost_price, stock_quantity,
     * meta_title, meta_description, meta_keywords, meta_canonical, primary_category_slug, brand_slug, category_slugs, tag_slugs,
     * image_ids, is_featured, is_active, created_by
     */
    public function export()
    {
        $products = Product::with([
            'primaryCategory',
            'brand',
            'faqs',
            'howTos',
            'variants',
        ])->get();

        // Load images từ image_ids JSON
        $allImageIds = [];
        foreach ($products as $product) {
            if (! empty($product->image_ids) && is_array($product->image_ids)) {
                $allImageIds = array_merge($allImageIds, $product->image_ids);
            }
        }
        $images = Image::whereIn('id', array_unique($allImageIds))->get()->keyBy('id');

        $categoryMap = Category::pluck('slug', 'id')->toArray();
        $brandMap = Brand::pluck('slug', 'id')->toArray();
        $tagMap = Tag::pluck('name', 'id')->toArray();

        $spreadsheet = new Spreadsheet;

        // Sheet 1: Products
        $this->buildProductsSheet($spreadsheet, $products, $categoryMap, $brandMap, $tagMap, $images);

        // Sheet 2: Images
        $this->buildImagesSheet($spreadsheet, $products, $images);

        // Sheet 3: FAQs
        $this->buildFaqsSheet($spreadsheet, $products);

        // Sheet 4: How-Tos
        $this->buildHowTosSheet($spreadsheet, $products);

        // Sheet 5: Variants
        $this->buildVariantsSheet($spreadsheet, $products);

        $fileName = 'products_export_'.now()->format('Y-m-d_H-i-s').'.xlsx';
        $tempDir = storage_path('app/tmp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $fullPath = $tempDir.'/'.$fileName;

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($fullPath);

        return response()->download($fullPath, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Xử lý import Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:10240', // max 10MB
        ]);

        $errors = [];

        try {
            $file = $request->file('excel_file');
            $spreadsheet = IOFactory::load($file->getRealPath());

            DB::beginTransaction();

            // Import Products (Sheet 1)
            $this->importProducts($spreadsheet, $errors);

            // Import Images (Sheet 2)
            $this->importImages($spreadsheet, $errors);

            // Import FAQs (Sheet 3)
            $this->importFaqs($spreadsheet, $errors);

            // Import How-Tos (Sheet 4)
            $this->importHowTos($spreadsheet, $errors);

            // Import Variants (Sheet 5)
            $this->importVariants($spreadsheet, $errors);

            DB::commit();

            // Sau khi import thành công, xóa cache tất cả sản phẩm để dữ liệu luôn mới
            $this->clearAllProductCaches();

            $logFile = $this->writeErrorLog($errors, $file->getClientOriginalName());

            $message = 'Import thành công!';
            if (! empty($errors)) {
                $message .= ' Có '.count($errors).' lỗi đã được ghi vào file log.';
            }

            return redirect()->back()
                ->with('success', $message)
                ->with('log_file', $logFile);

        } catch (\Exception $e) {
            DB::rollBack();
            $errors[] = [
                'type' => 'SYSTEM_ERROR',
                'sku' => 'N/A',
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile()),
            ];
            $logFile = $this->writeErrorLog($errors, $request->file('excel_file')->getClientOriginalName());

            return redirect()->back()
                ->with('error', 'Lỗi import: '.$e->getMessage())
                ->with('log_file', $logFile);
        }
    }

    /**
     * Build Products Sheet
     */
    private function buildProductsSheet(Spreadsheet $spreadsheet, $products, array $categoryMap, array $brandMap, array $tagMap, $images)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('products');

        $headers = [
            'sku', 'name', 'slug', 'description', 'short_description',
            'price', 'sale_price', 'cost_price', 'stock_quantity',
            'meta_title', 'meta_description', 'meta_keywords',
            'meta_canonical', 'primary_category_slug', 'brand_slug', 'category_slugs', 'tag_slugs',
            'image_ids', 'link_catalog', 'is_featured', 'is_active', 'created_by',
        ];
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($products as $product) {
            $primarySlug = optional($product->primaryCategory)->slug;
            $brandSlug = optional($product->brand)->slug;

            $categorySlugs = '';
            if (! empty($product->category_ids)) {
                $slugs = array_map(function ($id) use ($categoryMap) {
                    return $categoryMap[$id] ?? null;
                }, $product->category_ids ?? []);
                $categorySlugs = implode(',', array_filter($slugs));
            }

            $tagNames = '';
            if (! empty($product->tag_ids)) {
                $names = array_map(function ($id) use ($tagMap) {
                    return $tagMap[$id] ?? null;
                }, $product->tag_ids ?? []);
                $tagNames = implode(',', array_filter($names));
            }

            // Format image_ids: IMG1,IMG2,IMG3
            $imageIds = '';
            if (! empty($product->image_ids) && is_array($product->image_ids)) {
                $imageIds = implode(',', array_map(function ($id) {
                    return 'IMG'.$id;
                }, $product->image_ids));
            }

            // Format link_catalog: URL1,URL2,URL3 hoặc JSON
            $linkCatalog = '';
            if (! empty($product->link_catalog) && is_array($product->link_catalog)) {
                $linkCatalog = implode(',', $product->link_catalog);
            } elseif (is_string($product->link_catalog)) {
                $linkCatalog = $product->link_catalog;
            }

            $sheet->fromArray([
                $product->sku,
                $product->name,
                $product->slug,
                $product->description,
                $product->short_description,
                $product->price,
                $product->sale_price,
                $product->cost_price,
                $product->stock_quantity,
                $product->meta_title,
                $product->meta_description,
                is_array($product->meta_keywords) ? implode(',', $product->meta_keywords) : ($product->meta_keywords ?? ''),
                $product->meta_canonical,
                $primarySlug,
                $brandSlug,
                $categorySlugs,
                $tagNames,
                $imageIds,
                $linkCatalog,
                $product->is_featured ? 1 : 0,
                $product->is_active ? 1 : 0,
                $product->created_by,
            ], null, 'A'.$row);
            $row++;
        }
    }

    /**
     * Build Images Sheet
     */
    private function buildImagesSheet(Spreadsheet $spreadsheet, $products, $images)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('images');

        $headers = ['sku', 'image_key', 'url', 'title', 'notes', 'alt', 'is_primary', 'order'];
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($products as $product) {
            if (! empty($product->image_ids) && is_array($product->image_ids)) {
                foreach ($product->image_ids as $imageId) {
                    $image = $images->get($imageId);
                    if ($image) {
                        $sheet->fromArray([
                            $product->sku ?? '',
                            'IMG'.$image->id,
                            $image->url,
                            $image->title,
                            $image->notes,
                            $image->alt,
                            $image->is_primary ? 1 : 0,
                            $image->order,
                        ], null, 'A'.$row);
                        $row++;
                    }
                }
            }
        }
    }

    /**
     * Build FAQs Sheet
     */
    private function buildFaqsSheet(Spreadsheet $spreadsheet, $products)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('faqs');

        $headers = ['sku', 'question', 'answer', 'order'];
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($products as $product) {
            foreach ($product->faqs as $faq) {
                $sheet->fromArray([
                    $product->sku,
                    $faq->question,
                    $faq->answer,
                    $faq->order,
                ], null, 'A'.$row);
                $row++;
            }
        }
    }

    /**
     * Build How-Tos Sheet
     */
    private function buildHowTosSheet(Spreadsheet $spreadsheet, $products)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('how_tos');

        $headers = ['sku', 'title', 'description', 'steps', 'supplies', 'is_active'];
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($products as $product) {
            foreach ($product->howTos as $howTo) {
                $sheet->fromArray([
                    $product->sku,
                    $howTo->title,
                    $howTo->description,
                    ! empty($howTo->steps) ? json_encode($howTo->steps, JSON_UNESCAPED_UNICODE) : '',
                    ! empty($howTo->supplies) ? json_encode($howTo->supplies, JSON_UNESCAPED_UNICODE) : '',
                    $howTo->is_active ? 1 : 0,
                ], null, 'A'.$row);
                $row++;
            }
        }
    }

    /**
     * Build Variants Sheet
     */
    private function buildVariantsSheet(Spreadsheet $spreadsheet, $products): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('variants');

        $headers = [
            'product_sku',
            'variant_name',
            'variant_sku',
            'price',
            'sale_price',
            'cost_price',
            'stock_quantity',
            'image_id',
            'attributes_json',
            'is_active',
            'sort_order',
        ];

        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($products as $product) {
            if (! $product->variants || $product->variants->isEmpty()) {
                continue;
            }

            foreach ($product->variants as $variant) {
                $sheet->fromArray([
                    $product->sku,
                    $variant->name,
                    $variant->sku,
                    $variant->price,
                    $variant->sale_price,
                    $variant->cost_price,
                    $variant->stock_quantity,
                    $variant->image_id,
                    $variant->attributes ? json_encode($variant->attributes, JSON_UNESCAPED_UNICODE) : null,
                    $variant->is_active ? 1 : 0,
                    $variant->sort_order,
                ], null, 'A'.$row);
                $row++;
            }
        }
    }

    /**
     * Import Products
     */
    private function importProducts($spreadsheet, &$errors)
    {
        $sheet = $spreadsheet->getSheetByName('products');
        if (! $sheet) {
            Log::error('Import products: Sheet products không tồn tại', [
                'available_sheets' => $spreadsheet->getSheetNames(),
            ]);
            throw new \Exception('Sheet "products" không tồn tại!');
        }

        $rows = $sheet->toArray();
        $headers = array_shift($rows);

        $categoryMap = [];
        $brandMap = [];
        $tagCache = [];
        $processedCount = 0;
        $errorCount = 0;

        foreach ($rows as $rowIndex => $row) {
            if (empty($row[0])) {
                continue;
            } // Bỏ qua dòng trống (SKU rỗng)

            $sku = trim($row[0] ?? '');
            $name = trim($row[1] ?? '');
            $slug = trim($row[2] ?? '') ?: Str::slug($name);
            $description = trim($row[3] ?? '');
            $shortDescription = trim($row[4] ?? '');
            $price = (float) ($row[5] ?? 0);
            $salePrice = ! empty($row[6]) ? (float) $row[6] : null;
            $costPrice = ! empty($row[7]) ? (float) $row[7] : null;
            $stockQuantity = (int) ($row[8] ?? 0);
            $metaTitle = trim($row[9] ?? '');
            $metaDescription = trim($row[10] ?? '');
            $metaKeywordsRaw = trim($row[11] ?? '');
            $metaCanonical = trim($row[12] ?? '');
            $primaryCategorySlug = trim($row[13] ?? '');
            $brandSlug = trim($row[14] ?? '');
            $categorySlugs = trim($row[15] ?? '');
            $tagSlugs = trim($row[16] ?? '');
            $imageIdsRaw = trim($row[17] ?? '');
            $linkCatalogRaw = trim($row[18] ?? '');
            $isFeatured = isset($row[19]) ? (bool) $row[19] : false;
            $isActive = isset($row[20]) ? (bool) $row[20] : true;
            $createdBy = (int) ($row[21] ?? (Auth::check() ? Auth::id() : 1));

            if (empty($name)) {
                continue;
            }

            // Xử lý meta_keywords
            $metaKeywords = null;
            if (! empty($metaKeywordsRaw)) {
                $metaKeywords = array_filter(array_map('trim', explode(',', $metaKeywordsRaw)));
            }

            // Tính lại meta_canonical luôn theo slug và site_url (bỏ qua giá trị trong file Excel)
            $domainName = \App\Models\Setting::where('key', 'site_url')->value('value') ?? config('app.url');
            $domainName = rtrim($domainName, '/');
            $computedCanonical = $domainName.'/'.$slug;

            // Xử lý brand_id
            $brandId = null;
            if (! empty($brandSlug)) {
                if (isset($brandMap[$brandSlug])) {
                    $brandId = $brandMap[$brandSlug];
                } else {
                    $brand = Brand::where('slug', $brandSlug)->where('is_active', true)->first();
                    if ($brand) {
                        $brandId = $brand->id;
                        $brandMap[$brandSlug] = $brand->id;
                    } else {
                        $errors[] = [
                            'type' => 'BRAND_NOT_FOUND',
                            'sku' => $sku ?: 'N/A',
                            'brand_slug' => $brandSlug,
                            'message' => "Brand với slug '{$brandSlug}' không tồn tại hoặc không active.",
                            'row' => $rowIndex + 2,
                            'sheet' => 'products',
                        ];
                    }
                }
            }

            // Xử lý primary_category_id
            $primaryCategoryId = null;
            if (! empty($primaryCategorySlug)) {
                if (isset($categoryMap[$primaryCategorySlug])) {
                    $primaryCategoryId = $categoryMap[$primaryCategorySlug];
                } else {
                    $cat = Category::where('slug', $primaryCategorySlug)->first();
                    if ($cat) {
                        $primaryCategoryId = $cat->id;
                        $categoryMap[$primaryCategorySlug] = $cat->id;
                    } else {
                        $errors[] = [
                            'type' => 'PRIMARY_CATEGORY_NOT_FOUND',
                            'sku' => $sku ?: 'N/A',
                            'category_slug' => $primaryCategorySlug,
                            'message' => "Primary category với slug '{$primaryCategorySlug}' không tồn tại.",
                            'row' => $rowIndex + 2,
                            'sheet' => 'products',
                        ];
                    }
                }
            }

            // Xử lý category_ids
            $categoryIds = [];
            if (! empty($categorySlugs)) {
                $categorySlugArray = array_map('trim', explode(',', $categorySlugs));
                foreach ($categorySlugArray as $catSlug) {
                    if (empty($catSlug)) {
                        continue;
                    }
                    if (isset($categoryMap[$catSlug])) {
                        $categoryIds[] = $categoryMap[$catSlug];
                    } else {
                        $cat = Category::where('slug', $catSlug)->first();
                        if ($cat) {
                            $categoryIds[] = $cat->id;
                            $categoryMap[$catSlug] = $cat->id;
                        } else {
                            $errors[] = [
                                'type' => 'CATEGORY_NOT_FOUND',
                                'sku' => $sku ?: 'N/A',
                                'category_slug' => $catSlug,
                                'message' => "Category với slug '{$catSlug}' không tồn tại.",
                                'row' => $rowIndex + 2,
                                'sheet' => 'products',
                            ];
                        }
                    }
                }
            }

            // Xử lý tag_ids
            $tagIds = [];
            if (! empty($tagSlugs)) {
                $tagNames = array_map('trim', explode(',', $tagSlugs));
                foreach ($tagNames as $tagName) {
                    if (empty($tagName)) {
                        continue;
                    }
                    $slugTag = Str::slug($tagName);
                    if (empty($slugTag)) {
                        continue;
                    }

                    if (isset($tagCache[$slugTag])) {
                        $tagIds[] = $tagCache[$slugTag];

                        continue;
                    }

                    $tag = Tag::where('slug', $slugTag)->first();
                    if (! $tag) {
                        $tag = Tag::create([
                            'name' => $tagName,
                            'slug' => $slugTag,
                            'is_active' => true,
                            'entity_id' => 0,
                            'entity_type' => \App\Models\Product::class,
                        ]);
                    }

                    if ($tag) {
                        $tagCache[$slugTag] = $tag->id;
                        $tagIds[] = $tag->id;
                    }
                }
            }

            // Xử lý image_ids (sẽ được xử lý sau trong importImages)
            // Tạm thời để null, sẽ cập nhật sau khi import images
            $imageIds = null;

            // Xử lý link_catalog
            $linkCatalog = null;
            if (! empty($linkCatalogRaw)) {
                // Hỗ trợ cả comma-separated và JSON
                if (preg_match('/^\[.*\]$/', $linkCatalogRaw)) {
                    // JSON format
                    $decoded = json_decode($linkCatalogRaw, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $linkCatalog = array_filter(array_map('trim', $decoded));
                    }
                } else {
                    // Comma-separated format
                    $linkCatalog = array_filter(array_map('trim', explode(',', $linkCatalogRaw)));
                }
                $linkCatalog = ! empty($linkCatalog) ? array_values($linkCatalog) : null;
            }

            // Tìm product theo SKU
            $product = Product::where('sku', $sku)->first();

            // Chuẩn bị data để update/create
            $data = [
                'name' => $name,
                'slug' => $slug,
                'description' => $description ?: null,
                'short_description' => $shortDescription ?: null,
                'price' => $price,
                'sale_price' => $salePrice,
                'cost_price' => $costPrice,
                'stock_quantity' => $stockQuantity,
                'meta_title' => $metaTitle ?: null,
                'meta_description' => $metaDescription ?: null,
                'meta_keywords' => ! empty($metaKeywords) ? $metaKeywords : null,
                // Luôn cập nhật meta_canonical mới theo slug
                'meta_canonical' => $computedCanonical,
                'primary_category_id' => $primaryCategoryId,
                'brand_id' => $brandId,
                'category_ids' => ! empty($categoryIds) ? $categoryIds : null,
                'tag_ids' => ! empty($tagIds) ? $tagIds : null,
                'link_catalog' => $linkCatalog,
                'is_featured' => $isFeatured,
                'is_active' => $isActive,
                'created_by' => $createdBy,
            ];

            try {
                if ($product) {
                // Lưu slug cũ và is_active cũ để xóa cache
                $oldSlug = $product->slug;
                $oldIsActive = $product->is_active;

                // Update: chỉ cập nhật các trường thay đổi
                $updateData = [];
                foreach ($data as $key => $value) {
                    // So sánh giá trị cũ và mới
                    $oldValue = $product->$key;
                    if ($key === 'category_ids' || $key === 'tag_ids' || $key === 'meta_keywords') {
                        // So sánh array
                        $oldArray = is_array($oldValue) ? $oldValue : [];
                        $newArray = is_array($value) ? $value : [];
                        sort($oldArray);
                        sort($newArray);
                        if ($oldArray !== $newArray) {
                            $updateData[$key] = $value;
                        }
                    } elseif ($oldValue != $value) {
                        $updateData[$key] = $value;
                    }
                }

                // Nếu có thay đổi → xóa cache
                if (! empty($updateData)) {
                    $product->update($updateData);
                    $product->refresh();

                    // Xóa cache với slug cũ
                    Cache::forget('product_detail_'.$oldSlug);
                    Cache::forget('slug_type_'.$oldSlug);

                    // Nếu slug thay đổi, cũng xóa cache với slug mới
                    $newSlug = $product->slug;
                    if ($newSlug !== $oldSlug) {
                        Cache::forget('product_detail_'.$newSlug);
                        Cache::forget('slug_type_'.$newSlug);
                    } elseif (isset($updateData['is_active']) && $oldIsActive !== $product->is_active) {
                        // Nếu is_active thay đổi, invalidate slug_type cache
                        Cache::forget('slug_type_'.$newSlug);
                    }
                }
                // Nếu không có thay đổi → giữ nguyên cache
                } else {
                    // Create: tạo mới với SKU
                    $data['sku'] = $sku;
                    $newProduct = Product::create($data);

                    // Xóa cache với slug mới (tạo mới luôn cần xóa cache)
                    Cache::forget('product_detail_'.$newProduct->slug);
                    Cache::forget('slug_type_'.$newProduct->slug);
                }
                
                $processedCount++;
            } catch (\Exception $e) {
                $errorCount++;
                Log::error('❌ [IMPORT PRODUCTS] Lỗi khi xử lý sản phẩm', [
                    'sku' => $sku,
                    'row_index' => $rowIndex + 2,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                $errors[] = [
                    'type' => 'PRODUCT_IMPORT_ERROR',
                    'sku' => $sku,
                    'message' => $e->getMessage(),
                    'row' => $rowIndex + 2,
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine(),
                ];
            }
        }
    }

    /**
     * Import Images
     */
    private function importImages($spreadsheet, &$errors)
    {
        $sheet = $spreadsheet->getSheetByName('images');
        if (! $sheet) {
            return;
        } // Sheet tùy chọn

        $rows = $sheet->toArray();
        $headers = array_shift($rows);

        $imageMap = []; // image_key => image_id
        $productImageMap = []; // sku => [image_id1, image_id2, ...]

        foreach ($rows as $rowIndex => $row) {
            if (empty($row[0]) && empty($row[1])) {
                continue;
            }

            // Check if first column is SKU or image_key (backward compatibility)
            $sku = null;
            $imageKey = null;
            $url = null;
            $title = null;
            $notes = null;
            $alt = null;
            $isPrimary = false;
            $order = 0;

            // Detect format: if first column looks like SKU (not starting with IMG), it's new format
            $firstCol = trim($row[0] ?? '');
            if (! empty($firstCol) && ! preg_match('/^IMG\d+$/i', $firstCol)) {
                // New format: sku, image_key, url, title, notes, alt, is_primary, order
                $sku = $firstCol;
                $imageKey = trim($row[1] ?? '');
                $url = trim($row[2] ?? '');
                $title = trim($row[3] ?? '');
                $notes = trim($row[4] ?? '');
                $alt = trim($row[5] ?? '');
                $isPrimary = isset($row[6]) ? (bool) $row[6] : false;
                $order = (int) ($row[7] ?? 0);
            } else {
                // Old format: image_key, url, title, notes, alt, is_primary, order (no SKU)
                $imageKey = $firstCol;
                $url = trim($row[1] ?? '');
                $title = trim($row[2] ?? '');
                $notes = trim($row[3] ?? '');
                $alt = trim($row[4] ?? '');
                $isPrimary = isset($row[5]) ? (bool) $row[5] : false;
                $order = (int) ($row[6] ?? 0);
            }

            if (empty($imageKey) || empty($url)) {
                continue;
            }

            // Extract image ID from image_key (IMG123 -> 123)
            $imageId = null;
            if (preg_match('/^IMG(\d+)$/i', $imageKey, $matches)) {
                $imageId = (int) $matches[1];
            }

            if ($imageId) {
                // Update existing image
                $image = Image::find($imageId);
                if ($image) {
                    $image->update([
                        'url' => $url,
                        'title' => $title ?: null,
                        'notes' => $notes ?: null,
                        'alt' => $alt ?: null,
                        'is_primary' => $isPrimary,
                        'order' => $order,
                    ]);
                    $imageMap[$imageKey] = $image->id;
                } else {
                    // Create new image
                    $image = Image::create([
                        'url' => $url,
                        'title' => $title ?: null,
                        'notes' => $notes ?: null,
                        'alt' => $alt ?: null,
                        'is_primary' => $isPrimary,
                        'order' => $order,
                    ]);
                    $imageMap[$imageKey] = $image->id;
                }
            } else {
                // Create new image without ID
                $image = Image::create([
                    'url' => $url,
                    'title' => $title ?: null,
                    'notes' => $notes ?: null,
                    'alt' => $alt ?: null,
                    'is_primary' => $isPrimary,
                    'order' => $order,
                ]);
                $imageMap[$imageKey] = $image->id;
            }

            // If SKU is provided, add to product image map
            if (! empty($sku)) {
                $finalImageId = $imageMap[$imageKey] ?? $image->id;
                if (! isset($productImageMap[$sku])) {
                    $productImageMap[$sku] = [];
                }
                $productImageMap[$sku][] = $finalImageId;
            }
        }

        // Cập nhật image_ids cho products từ SKU trong sheet images
        foreach ($productImageMap as $sku => $imageIds) {
            $product = Product::where('sku', $sku)->first();
            if ($product) {
                $oldImageIds = $product->image_ids ?? [];
                $newImageIds = array_unique($imageIds);

                // So sánh image_ids cũ và mới
                $oldArray = is_array($oldImageIds) ? $oldImageIds : [];
                $newArray = is_array($newImageIds) ? $newImageIds : [];
                sort($oldArray);
                sort($newArray);

                // Chỉ update nếu có thay đổi
                if ($oldArray !== $newArray) {
                    $product->update(['image_ids' => $newImageIds]);
                    // Xóa cache vì image_ids đã thay đổi
                    Cache::forget('product_detail_'.$product->slug);
                    Cache::forget('slug_type_'.$product->slug);
                }
            } else {
                $errors[] = [
                    'type' => 'PRODUCT_NOT_FOUND',
                    'sku' => $sku,
                    'message' => "Không tìm thấy sản phẩm với SKU '{$sku}' trong sheet images. Đã bỏ qua ảnh này.",
                    'row' => null,
                    'sheet' => 'images',
                ];
            }
        }

        // Fallback: Cập nhật image_ids từ sheet products (nếu không có SKU trong sheet images)
        if (empty($productImageMap)) {
            $sheet = $spreadsheet->getSheetByName('products');
            if ($sheet) {
                $rows = $sheet->toArray();
                array_shift($rows); // Bỏ header

                foreach ($rows as $row) {
                    if (empty($row[0])) {
                        continue;
                    }
                    $sku = trim($row[0] ?? '');
                    // Index 17 vì đã thêm brand_slug vào vị trí 14 (sau primary_category_slug)
                    $imageIdsRaw = trim($row[17] ?? '');

                    if (empty($sku) || empty($imageIdsRaw)) {
                        continue;
                    }

                    $product = Product::where('sku', $sku)->first();
                    if (! $product) {
                        continue;
                    }

                    // Parse image_ids: IMG1,IMG2,IMG3 -> [1,2,3]
                    $imageKeys = array_map('trim', explode(',', $imageIdsRaw));
                    $imageIds = [];
                    foreach ($imageKeys as $imageKey) {
                        if (isset($imageMap[$imageKey])) {
                            $imageIds[] = $imageMap[$imageKey];
                        } elseif (preg_match('/^IMG(\d+)$/i', $imageKey, $matches)) {
                            $imageIds[] = (int) $matches[1];
                        }
                    }

                    if (! empty($imageIds)) {
                        $oldImageIds = $product->image_ids ?? [];
                        $newImageIds = array_unique($imageIds);

                        // So sánh image_ids cũ và mới
                        $oldArray = is_array($oldImageIds) ? $oldImageIds : [];
                        $newArray = is_array($newImageIds) ? $newImageIds : [];
                        sort($oldArray);
                        sort($newArray);

                        // Chỉ update nếu có thay đổi
                        if ($oldArray !== $newArray) {
                            $product->update(['image_ids' => $newImageIds]);
                            // Xóa cache vì image_ids đã thay đổi
                            Cache::forget('product_detail_'.$product->slug);
                            Cache::forget('slug_type_'.$product->slug);
                        }
                    }
                }
            }
        }
    }

    /**
     * Import FAQs
     */
    private function importFaqs($spreadsheet, &$errors)
    {
        $sheet = $spreadsheet->getSheetByName('faqs');
        if (! $sheet) {
            return;
        } // Sheet tùy chọn

        $rows = $sheet->toArray();
        $headers = array_shift($rows);

        foreach ($rows as $rowIndex => $row) {
            if (empty($row[0])) {
                continue;
            }

            $sku = trim($row[0] ?? '');
            $question = trim($row[1] ?? '');
            $answer = trim($row[2] ?? '');
            $order = (int) ($row[3] ?? 0);

            if (empty($sku) || empty($question)) {
                continue;
            }

            $product = Product::where('sku', $sku)->first();
            if (! $product) {
                $errors[] = [
                    'type' => 'PRODUCT_NOT_FOUND',
                    'sku' => $sku,
                    'message' => "Không tìm thấy sản phẩm với SKU '{$sku}'. Đã bỏ qua FAQ này.",
                    'row' => $rowIndex + 2,
                    'sheet' => 'faqs',
                ];

                continue;
            }

            // Kiểm tra xem FAQ đã tồn tại chưa
            $existingFaq = ProductFaq::where('product_id', $product->id)
                ->where('question', $question)
                ->first();

            $wasCreated = ! $existingFaq;
            $wasChanged = false;

            if ($existingFaq) {
                // So sánh dữ liệu cũ và mới
                $oldAnswer = $existingFaq->answer;
                $oldOrder = $existingFaq->order;
                if ($oldAnswer != $answer || $oldOrder != $order) {
                    $wasChanged = true;
                }
            }

            // Update or create FAQ
            ProductFaq::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'question' => $question,
                ],
                [
                    'answer' => $answer ?: null,
                    'order' => $order,
                ]
            );

            // Nếu FAQ được tạo mới hoặc thay đổi → xóa cache
            if ($wasCreated || $wasChanged) {
                Cache::forget('product_detail_'.$product->slug);
                Cache::forget('slug_type_'.$product->slug);
            }
        }
    }

    /**
     * Import Variants
     */
    private function importVariants($spreadsheet, array &$errors): void
    {
        $sheet = $spreadsheet->getSheetByName('variants');
        if (! $sheet) {
            // Không có sheet variants thì bỏ qua (giữ logic cũ)
            return;
        }

        $rows = $sheet->toArray();
        $headers = array_shift($rows);

        // Map header -> index
        $headerIndex = [];
        foreach ($headers as $index => $header) {
            $headerIndex[strtolower(trim($header))] = $index;
        }

        $requiredCols = ['product_sku', 'variant_name'];
        foreach ($requiredCols as $col) {
            if (! array_key_exists($col, $headerIndex)) {
                throw new \Exception("Sheet \"variants\" thiếu cột bắt buộc: {$col}");
            }
        }

        $processed = []; // product_id => [variant_ids_kept]

        foreach ($rows as $rowIndex => $row) {
            $rowNumber = $rowIndex + 2; // +2 vì header ở dòng 1

            $productSku = trim((string) ($row[$headerIndex['product_sku']] ?? ''));
            $variantName = trim((string) ($row[$headerIndex['variant_name']] ?? ''));
            $variantSku = array_key_exists('variant_sku', $headerIndex) ? trim((string) ($row[$headerIndex['variant_sku']] ?? '')) : null;
            $price = (float) ($row[$headerIndex['price']] ?? 0);
            $salePrice = array_key_exists('sale_price', $headerIndex) ? $row[$headerIndex['sale_price']] : null;
            $costPrice = array_key_exists('cost_price', $headerIndex) ? $row[$headerIndex['cost_price']] : null;
            $stockQuantity = array_key_exists('stock_quantity', $headerIndex) ? $row[$headerIndex['stock_quantity']] : null;
            $imageId = array_key_exists('image_id', $headerIndex) ? $row[$headerIndex['image_id']] : null;
            $attributesJson = array_key_exists('attributes_json', $headerIndex) ? $row[$headerIndex['attributes_json']] : null;
            $isActive = array_key_exists('is_active', $headerIndex) ? $row[$headerIndex['is_active']] : 1;
            $sortOrder = array_key_exists('sort_order', $headerIndex) ? (int) $row[$headerIndex['sort_order']] : 0;

            if (empty($productSku) || empty($variantName) || $price <= 0) {
                continue; // Bỏ qua dòng không hợp lệ
            }

            $product = Product::where('sku', $productSku)->first();
            if (! $product) {
                $errors[] = [
                    'type' => 'PRODUCT_NOT_FOUND',
                    'sku' => $productSku,
                    'message' => "Không tìm thấy sản phẩm với SKU '{$productSku}' khi import biến thể.",
                    'row' => $rowNumber,
                    'sheet' => 'variants',
                ];

                continue;
            }

            // Parse attributes JSON
            $attributes = null;
            if (! empty($attributesJson)) {
                $decoded = json_decode($attributesJson, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $attributes = $decoded;
                } else {
                    $errors[] = [
                        'type' => 'INVALID_ATTRIBUTES_JSON',
                        'sku' => $productSku,
                        'message' => "JSON attributes không hợp lệ tại dòng {$rowNumber}: {$attributesJson}",
                        'row' => $rowNumber,
                        'sheet' => 'variants',
                    ];
                }
            }

            // Lấy variant theo sku nếu có, nếu không dùng name
            $variantQuery = ProductVariant::where('product_id', $product->id);
            if (! empty($variantSku)) {
                $variantQuery->where('sku', $variantSku);
            } else {
                $variantQuery->where('name', $variantName);
            }
            $variant = $variantQuery->first();

            // Chuẩn bị data
            $variantData = [
                'name' => $variantName,
                'sku' => $variantSku ?: null,
                'price' => (float) $price,
                'sale_price' => $salePrice !== null && $salePrice !== '' ? (float) $salePrice : null,
                'cost_price' => $costPrice !== null && $costPrice !== '' ? (float) $costPrice : null,
                'stock_quantity' => $stockQuantity !== null && $stockQuantity !== '' ? (int) $stockQuantity : null,
                'image_id' => $imageId && is_numeric($imageId) ? (int) $imageId : null,
                'attributes' => $attributes,
                'is_active' => (bool) $isActive,
                'sort_order' => $sortOrder,
            ];

            if ($variant) {
                $variant->update($variantData);
                $variantId = $variant->id;
            } else {
                $variantId = ProductVariant::create(array_merge($variantData, [
                    'product_id' => $product->id,
                ]))->id;
            }

            // Ghi nhận variant đã xử lý
            if (! isset($processed[$product->id])) {
                $processed[$product->id] = [];
            }
            $processed[$product->id][] = $variantId;

            // Clear cache product
            Cache::forget('product_detail_'.$product->slug);
            Cache::forget('slug_type_'.$product->slug);
        }

        // Xóa các biến thể không có trong file cho từng sản phẩm đã xử lý
        foreach ($processed as $productId => $keepIds) {
            ProductVariant::where('product_id', $productId)
                ->whereNotIn('id', $keepIds)
                ->delete();

            // Xóa cache sản phẩm
            $product = Product::find($productId);
            if ($product) {
                Cache::forget('product_detail_'.$product->slug);
                Cache::forget('slug_type_'.$product->slug);
            }
        }
    }

    /**
     * Import How-Tos
     */
    private function importHowTos($spreadsheet, &$errors)
    {
        $sheet = $spreadsheet->getSheetByName('how_tos');
        if (! $sheet) {
            return;
        } // Sheet tùy chọn

        $rows = $sheet->toArray();
        $headers = array_shift($rows);

        foreach ($rows as $rowIndex => $row) {
            if (empty($row[0])) {
                continue;
            }

            $sku = trim($row[0] ?? '');
            $title = trim($row[1] ?? '');
            $description = trim($row[2] ?? '');
            $stepsRaw = trim($row[3] ?? '');
            $suppliesRaw = trim($row[4] ?? '');
            $isActive = isset($row[5]) ? (bool) $row[5] : true;

            if (empty($sku) || empty($title)) {
                continue;
            }

            $product = Product::where('sku', $sku)->first();
            if (! $product) {
                $errors[] = [
                    'type' => 'PRODUCT_NOT_FOUND',
                    'sku' => $sku,
                    'message' => "Không tìm thấy sản phẩm với SKU '{$sku}'. Đã bỏ qua How-To này.",
                    'row' => $rowIndex + 2,
                    'sheet' => 'how_tos',
                ];

                continue;
            }

            // Xử lý steps và supplies (JSON)
            $steps = null;
            if (! empty($stepsRaw)) {
                $decoded = json_decode($stepsRaw, true);
                $steps = $decoded ?: array_filter(array_map('trim', explode("\n", $stepsRaw)));
            }

            $supplies = null;
            if (! empty($suppliesRaw)) {
                $decoded = json_decode($suppliesRaw, true);
                $supplies = $decoded ?: array_filter(array_map('trim', explode(',', $suppliesRaw)));
            }

            // Kiểm tra xem How-To đã tồn tại chưa
            $existingHowTo = ProductHowTo::where('product_id', $product->id)
                ->where('title', $title)
                ->first();

            $wasCreated = ! $existingHowTo;
            $wasChanged = false;

            if ($existingHowTo) {
                // So sánh dữ liệu cũ và mới
                $oldDescription = $existingHowTo->description;
                $oldSteps = $existingHowTo->steps ?? [];
                $oldSupplies = $existingHowTo->supplies ?? [];
                $oldIsActive = $existingHowTo->is_active;

                $oldStepsArray = is_array($oldSteps) ? $oldSteps : [];
                $newStepsArray = is_array($steps) ? $steps : [];
                sort($oldStepsArray);
                sort($newStepsArray);

                $oldSuppliesArray = is_array($oldSupplies) ? $oldSupplies : [];
                $newSuppliesArray = is_array($supplies) ? $supplies : [];
                sort($oldSuppliesArray);
                sort($newSuppliesArray);

                if ($oldDescription != $description ||
                    $oldStepsArray !== $newStepsArray ||
                    $oldSuppliesArray !== $newSuppliesArray ||
                    $oldIsActive != $isActive) {
                    $wasChanged = true;
                }
            }

            // Update or create How-To
            ProductHowTo::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'title' => $title,
                ],
                [
                    'description' => $description ?: null,
                    'steps' => $steps,
                    'supplies' => $supplies,
                    'is_active' => $isActive,
                ]
            );

            // Nếu How-To được tạo mới hoặc thay đổi → xóa cache
            if ($wasCreated || $wasChanged) {
                Cache::forget('product_detail_'.$product->slug);
                Cache::forget('slug_type_'.$product->slug);
            }
        }
    }

    /**
     * Ghi log lỗi vào file txt
     */
    private function writeErrorLog($errors, $originalFileName)
    {
        if (empty($errors)) {
            return null;
        }

        $logDir = storage_path('logs/imports');
        if (! is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $timestamp = date('Y-m-d_H-i-s');
        $baseName = pathinfo($originalFileName, PATHINFO_FILENAME);
        $logFileName = "import_errors_{$baseName}_{$timestamp}.txt";
        $logPath = $logDir.'/'.$logFileName;

        $content = "========================================\n";
        $content .= "LOG LỖI IMPORT EXCEL\n";
        $content .= "========================================\n";
        $content .= "File Excel: {$originalFileName}\n";
        $content .= 'Thời gian: '.date('Y-m-d H:i:s')."\n";
        $content .= 'Tổng số lỗi: '.count($errors)."\n";
        $content .= "========================================\n\n";

        foreach ($errors as $index => $error) {
            $content .= '['.($index + 1).'] '.($error['type'] ?? 'UNKNOWN')."\n";
            $content .= 'Sheet: '.($error['sheet'] ?? 'N/A').' | ';
            $content .= 'Dòng: '.($error['row'] ?? 'N/A').' | ';
            $content .= 'SKU: '.($error['sku'] ?? 'N/A')."\n";
            $content .= 'Mô tả: '.($error['message'] ?? 'Không có mô tả')."\n";
            $content .= "\n";
        }

        file_put_contents($logPath, $content);

        return $logFileName;
    }

    /**
     * Xóa cache cho tất cả sản phẩm (product_detail_*, slug_type_*, related_products_*, vouchers_for_product_*)
     * để đảm bảo dữ liệu luôn mới sau mỗi lần import Excel.
     */
    private function clearAllProductCaches(): void
    {
        Product::query()
            ->select('id', 'slug')
            ->chunkById(200, function ($products): void {
                foreach ($products as $product) {
                    Cache::forget('product_detail_'.$product->slug);
                    Cache::forget('slug_type_'.$product->slug);
                    Cache::forget('related_products_'.$product->id);
                    Cache::forget('vouchers_for_product_'.$product->id);
                }
            });
    }

    // ============================================
    // API METHODS CHO EXPORT/IMPORT VỚI FILTER
    // ============================================

    /**
     * Bắt đầu export sản phẩm theo filter (API)
     */
    public function startExportWithFilter(Request $request): JsonResponse
    {
        $request->validate([
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
            'brand_ids' => 'nullable|array',
            'brand_ids.*' => 'integer|exists:brands,id',
        ]);

        try {
            // Đếm tổng số sản phẩm cần export
            $query = $this->buildFilterQuery($request);
            $totalProducts = $query->count();

            if ($totalProducts === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có sản phẩm nào phù hợp với bộ lọc.',
                ], 400);
            }

            // Tạo session ID cho export
            $sessionId = 'export_'.time().'_'.uniqid();

            // Lưu thông tin export vào cache (expire sau 1 giờ)
            Cache::put("export_{$sessionId}", [
                'category_ids' => $request->input('category_ids', []),
                'brand_ids' => $request->input('brand_ids', []),
                'total_products' => $totalProducts,
                'processed' => 0,
                'status' => 'processing',
                'created_at' => now()->toDateTimeString(),
            ], now()->addHour());

            return response()->json([
                'success' => true,
                'session_id' => $sessionId,
                'total_products' => $totalProducts,
                'message' => 'Bắt đầu xuất sản phẩm...',
            ]);

        } catch (\Exception $e) {
            Log::error('Export start error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi bắt đầu xuất: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Xử lý export chunk (được gọi nhiều lần)
     */
    public function processExportChunk(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string',
            'chunk' => 'required|integer|min:0',
            'chunk_size' => 'required|integer|min:1|max:500',
        ]);

        $sessionId = $request->input('session_id');
        $chunk = (int) $request->input('chunk');
        $chunkSize = (int) $request->input('chunk_size', 100);

        $cacheKey = "export_{$sessionId}";
        $exportData = Cache::get($cacheKey);

        if (! $exportData) {
            return response()->json([
                'success' => false,
                'message' => 'Session không tồn tại hoặc đã hết hạn.',
            ], 404);
        }

        if ($exportData['status'] === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Export đã bị hủy.',
                'cancelled' => true,
            ], 400);
        }

        try {
            // Build query với filter
            $request->merge([
                'category_ids' => $exportData['category_ids'] ?? [],
                'brand_ids' => $exportData['brand_ids'] ?? [],
            ]);
            $query = $this->buildFilterQuery($request);

            // Kiểm tra xem chunk này đã được xử lý chưa (tránh xử lý trùng)
            $chunkFile = storage_path("app/exports/{$sessionId}_chunk_{$chunk}.json");
            if (file_exists($chunkFile)) {
                // Chunk đã được xử lý, chỉ cập nhật progress
                $processed = $exportData['processed'] ?? 0;
                $progress = $exportData['total_products'] > 0
                    ? ($processed / $exportData['total_products']) * 100
                    : 0;

                return response()->json([
                    'success' => true,
                    'processed' => $processed,
                    'total' => $exportData['total_products'],
                    'progress' => round($progress, 2),
                    'completed' => false,
                    'message' => 'Chunk đã được xử lý',
                ]);
            }

            // Lấy chunk sản phẩm
            $products = $query->skip($chunk * $chunkSize)
                ->take($chunkSize)
                ->with([
                    'primaryCategory',
                    'brand',
                    'faqs',
                    'howTos',
                    'variants',
                ])
                ->get();

            if ($products->isEmpty()) {
                // Không còn sản phẩm nào, kiểm tra xem đã xử lý hết chưa
                $totalProcessed = $exportData['processed'] ?? 0;
                
                // Nếu đã xử lý đủ số lượng, finalize
                if ($totalProcessed >= $exportData['total_products']) {
                    // Đảm bảo finalize chỉ được gọi 1 lần
                    if ($exportData['status'] !== 'finalizing' && $exportData['status'] !== 'completed') {
                        Cache::put($cacheKey, array_merge($exportData, [
                            'status' => 'finalizing',
                        ]), now()->addHour());
                        
                        // Finalize ngay lập tức (không async)
                        try {
                            $this->finalizeExportWithFilter($sessionId, $exportData);
                            
                            // Kiểm tra file đã được tạo chưa
                            $filePath = storage_path("app/exports/{$sessionId}.xlsx");
                            if (!file_exists($filePath)) {
                                throw new \Exception('File export chưa được tạo.');
                            }
                        } catch (\Exception $e) {
                            Log::error('Finalize export error', [
                                'session_id' => $sessionId,
                                'error' => $e->getMessage(),
                            ]);
                            Cache::put($cacheKey, array_merge($exportData, [
                                'status' => 'error',
                                'error' => $e->getMessage(),
                            ]), now()->addHour());
                            throw $e;
                        }
                    }
                    
                    // Kiểm tra lại file đã tồn tại chưa
                    $filePath = storage_path("app/exports/{$sessionId}.xlsx");
                    if (file_exists($filePath)) {
                        return response()->json([
                            'success' => true,
                            'completed' => true,
                            'processed' => $totalProcessed,
                            'total' => $exportData['total_products'],
                            'file_url' => $this->getExportFileUrl($sessionId),
                        ]);
                    } else {
                        // File chưa sẵn sàng, trả về đang xử lý
                        return response()->json([
                            'success' => true,
                            'processed' => $totalProcessed,
                            'total' => $exportData['total_products'],
                            'progress' => 99,
                            'completed' => false,
                            'message' => 'Đang tạo file Excel...',
                        ]);
                    }
                }

                // Chưa đủ, tiếp tục
                return response()->json([
                    'success' => true,
                    'processed' => $totalProcessed,
                    'total' => $exportData['total_products'],
                    'progress' => round(($totalProcessed / $exportData['total_products']) * 100, 2),
                    'completed' => false,
                ]);
            }

            // Lưu product IDs vào file tạm (chỉ lưu IDs để tiết kiệm bộ nhớ)
            $this->saveExportChunk($sessionId, $chunk, $products->pluck('id')->toArray());

            // Cập nhật progress
            $processed = ($exportData['processed'] ?? 0) + $products->count();
            Cache::put($cacheKey, array_merge($exportData, [
                'processed' => $processed,
                'last_chunk' => $chunk,
            ]), now()->addHour());

            $progress = ($processed / $exportData['total_products']) * 100;

            // Kiểm tra xem đã xử lý hết chưa
            if ($processed >= $exportData['total_products']) {
                // Đảm bảo finalize chỉ được gọi 1 lần
                if ($exportData['status'] !== 'finalizing' && $exportData['status'] !== 'completed') {
                    Cache::put($cacheKey, array_merge($exportData, [
                        'status' => 'finalizing',
                        'processed' => $processed,
                    ]), now()->addHour());
                    
                    // Finalize ngay lập tức (không async)
                    try {
                        $this->finalizeExportWithFilter($sessionId, array_merge($exportData, ['processed' => $processed]));
                        
                        // Kiểm tra file đã được tạo chưa
                        $filePath = storage_path("app/exports/{$sessionId}.xlsx");
                        if (!file_exists($filePath)) {
                            throw new \Exception('File export chưa được tạo.');
                        }
                    } catch (\Exception $e) {
                        Log::error('Finalize export error', [
                            'session_id' => $sessionId,
                            'error' => $e->getMessage(),
                        ]);
                        Cache::put($cacheKey, array_merge($exportData, [
                            'status' => 'error',
                            'error' => $e->getMessage(),
                            'processed' => $processed,
                        ]), now()->addHour());
                        throw $e;
                    }
                }
                
                // Kiểm tra lại file đã tồn tại chưa
                $filePath = storage_path("app/exports/{$sessionId}.xlsx");
                if (file_exists($filePath)) {
                    return response()->json([
                        'success' => true,
                        'completed' => true,
                        'processed' => $processed,
                        'total' => $exportData['total_products'],
                        'file_url' => $this->getExportFileUrl($sessionId),
                    ]);
                } else {
                    // File chưa sẵn sàng, trả về đang xử lý
                    return response()->json([
                        'success' => true,
                        'processed' => $processed,
                        'total' => $exportData['total_products'],
                        'progress' => 99,
                        'completed' => false,
                        'message' => 'Đang tạo file Excel...',
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'processed' => $processed,
                'total' => $exportData['total_products'],
                'progress' => round($progress, 2),
                'completed' => false,
            ]);

        } catch (\Exception $e) {
            Log::error('Export chunk error', [
                'session_id' => $sessionId,
                'chunk' => $chunk,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Cache::put($cacheKey, array_merge($exportData, [
                'status' => 'error',
                'error' => $e->getMessage(),
            ]), now()->addHour());

            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xử lý chunk: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Hủy export
     */
    public function cancelExport(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        $sessionId = $request->input('session_id');
        $cacheKey = "export_{$sessionId}";
        $exportData = Cache::get($cacheKey);

        if ($exportData) {
            Cache::put($cacheKey, array_merge($exportData, [
                'status' => 'cancelled',
            ]), now()->addHour());

            // Xóa file tạm nếu có
            $this->cleanupExportFiles($sessionId);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã hủy xuất sản phẩm.',
        ]);
    }

    /**
     * Lấy progress của export
     */
    public function getExportProgress(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        $sessionId = $request->input('session_id');
        $cacheKey = "export_{$sessionId}";
        $exportData = Cache::get($cacheKey);

        if (! $exportData) {
            return response()->json([
                'success' => false,
                'message' => 'Session không tồn tại.',
            ], 404);
        }

        $progress = $exportData['total_products'] > 0
            ? ($exportData['processed'] / $exportData['total_products']) * 100
            : 0;

        return response()->json([
            'success' => true,
            'processed' => $exportData['processed'] ?? 0,
            'total' => $exportData['total_products'] ?? 0,
            'progress' => round($progress, 2),
            'status' => $exportData['status'] ?? 'processing',
            'completed' => $exportData['status'] === 'completed',
            'cancelled' => $exportData['status'] === 'cancelled',
            'file_url' => $exportData['status'] === 'completed' ? $this->getExportFileUrl($sessionId) : null,
        ]);
    }

    /**
     * Download file export
     */
    public function downloadExport(string $sessionId)
    {
        $filePath = storage_path("app/exports/{$sessionId}.xlsx");

        if (! file_exists($filePath)) {
            Log::warning('Export file not found', [
                'session_id' => $sessionId,
                'file_path' => $filePath,
            ]);
            
            // Kiểm tra xem có đang finalize không
            $cacheKey = "export_{$sessionId}";
            $exportData = Cache::get($cacheKey);
            
            if ($exportData && $exportData['status'] === 'finalizing') {
                // File đang được tạo, trả về JSON thay vì HTML error page
                return response()->json([
                    'success' => false,
                    'message' => 'File đang được tạo, vui lòng đợi thêm vài giây.',
                    'status' => 'finalizing',
                ], 202); // 202 Accepted
            }
            
            abort(404, 'File không tồn tại hoặc đã bị xóa.');
        }

        // Kiểm tra file size
        $fileSize = filesize($filePath);
        if ($fileSize === false || $fileSize === 0) {
            Log::warning('Export file is empty', [
                'session_id' => $sessionId,
                'file_path' => $filePath,
                'file_size' => $fileSize,
            ]);
            abort(500, 'File export rỗng hoặc không hợp lệ.');
        }

        $fileName = 'products_export_'.now()->format('Y-m-d_H-i-s').'.xlsx';

        return response()->download($filePath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Build query với filter (dùng cho export với filter)
     */
    protected function buildFilterQuery(Request $request)
    {
        $query = Product::query();

        // Filter theo category (sử dụng primary_category_id hoặc category_ids JSON)
        if ($request->filled('category_ids') && is_array($request->input('category_ids'))) {
            $categoryIds = array_filter($request->input('category_ids'));
            if (! empty($categoryIds)) {
                $query->where(function ($q) use ($categoryIds) {
                    $q->whereIn('primary_category_id', $categoryIds)
                        ->orWhereJsonContains('category_ids', $categoryIds);
                });
            }
        }

        // Filter theo brand
        if ($request->filled('brand_ids') && is_array($request->input('brand_ids'))) {
            $brandIds = array_filter($request->input('brand_ids'));
            if (! empty($brandIds)) {
                $query->whereIn('brand_id', $brandIds);
            }
        }

        return $query->orderBy('id');
    }

    /**
     * Lưu chunk vào file tạm (chỉ lưu product IDs)
     */
    protected function saveExportChunk(string $sessionId, int $chunk, array $productIds)
    {
        $exportDir = storage_path('app/exports');
        if (! is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        $chunkFile = "{$exportDir}/{$sessionId}_chunk_{$chunk}.json";
        file_put_contents($chunkFile, json_encode($productIds, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Hoàn thành export và merge tất cả chunks
     */
    protected function finalizeExportWithFilter(string $sessionId, array $exportData)
    {
        $cacheKey = "export_{$sessionId}";
        
        // Kiểm tra xem đã finalize chưa (tránh gọi nhiều lần)
        $currentData = Cache::get($cacheKey);
        if ($currentData && $currentData['status'] === 'completed') {
            return;
        }

        // Đánh dấu đang finalize
        Cache::put($cacheKey, array_merge($exportData, [
            'status' => 'finalizing',
        ]), now()->addHour());

        $exportDir = storage_path('app/exports');
        $chunkFiles = glob("{$exportDir}/{$sessionId}_chunk_*.json");
        sort($chunkFiles);

        if (empty($chunkFiles)) {
            Log::warning('Export: No chunk files found', ['session_id' => $sessionId]);
            throw new \Exception('Không tìm thấy file chunks để xuất.');
        }

        // Load tất cả product IDs từ chunks
        $allProductIds = [];
        foreach ($chunkFiles as $chunkFile) {
            if (!file_exists($chunkFile)) {
                continue;
            }
            $productIds = json_decode(file_get_contents($chunkFile), true);
            if (is_array($productIds) && !empty($productIds)) {
                $allProductIds = array_merge($allProductIds, $productIds);
            }
        }

        if (empty($allProductIds)) {
            Log::warning('Export: No product IDs in chunks', ['session_id' => $sessionId]);
            throw new \Exception('Không có sản phẩm nào trong các chunks.');
        }

        // Load lại products từ database với đầy đủ relationships
        $products = Product::whereIn('id', array_unique($allProductIds))
            ->with([
                'primaryCategory',
                'brand',
                'faqs',
                'howTos',
                'variants',
            ])
            ->orderBy('id')
            ->get();

        // Load images từ image_ids JSON
        $allImageIds = [];
        foreach ($products as $product) {
            if (! empty($product->image_ids) && is_array($product->image_ids)) {
                $allImageIds = array_merge($allImageIds, $product->image_ids);
            }
        }
        $images = Image::whereIn('id', array_unique($allImageIds))->get()->keyBy('id');

        $categoryMap = Category::pluck('slug', 'id')->toArray();
        $brandMap = Brand::pluck('slug', 'id')->toArray();
        $tagMap = Tag::pluck('name', 'id')->toArray();

        if (empty($allProductIds)) {
            Log::warning('Export: No products to export', ['session_id' => $sessionId]);
            throw new \Exception('Không có sản phẩm nào để xuất.');
        }

        if ($products->isEmpty()) {
            Log::warning('Export: Products not found in database', [
                'session_id' => $sessionId,
                'product_ids' => $allProductIds,
            ]);
            throw new \Exception('Không tìm thấy sản phẩm trong database.');
        }

        $spreadsheet = new Spreadsheet;

        // Build các sheets giống như export() cũ
        $this->buildProductsSheet($spreadsheet, $products, $categoryMap, $brandMap, $tagMap, $images);
        $this->buildImagesSheet($spreadsheet, $products, $images);
        $this->buildFaqsSheet($spreadsheet, $products);
        $this->buildHowTosSheet($spreadsheet, $products);
        $this->buildVariantsSheet($spreadsheet, $products);

        $filePath = "{$exportDir}/{$sessionId}.xlsx";
        
        // Xóa file cũ nếu có
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        // Đảm bảo file đã được ghi xong
        if (! file_exists($filePath)) {
            throw new \Exception('Không thể tạo file export.');
        }

        // Kiểm tra file size (phải > 0)
        $fileSize = filesize($filePath);
        if ($fileSize === false || $fileSize === 0) {
            throw new \Exception('File export rỗng hoặc không hợp lệ.');
        }

        // Xóa chunk files
        foreach ($chunkFiles as $chunkFile) {
            @unlink($chunkFile);
        }

        // Cập nhật status
        $cacheKey = "export_{$sessionId}";
        Cache::put($cacheKey, array_merge($exportData, [
            'status' => 'completed',
            'file_path' => $filePath,
            'completed_at' => now()->toDateTimeString(),
        ]), now()->addHour());
    }

    /**
     * Lấy URL download file
     */
    protected function getExportFileUrl(string $sessionId): string
    {
        return route('admin.products.export-import.download', ['sessionId' => $sessionId]);
    }

    /**
     * Cleanup export files
     */
    protected function cleanupExportFiles(string $sessionId)
    {
        $exportDir = storage_path('app/exports');
        $files = glob("{$exportDir}/{$sessionId}*");
        foreach ($files as $file) {
            @unlink($file);
        }
    }

    // ============================================
    // API METHODS CHO IMPORT VỚI FILE UPLOAD
    // ============================================

    /**
     * Bắt đầu import Excel với file upload (API)
     */
    public function startImportWithFile(Request $request): JsonResponse
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:10240', // max 10MB
        ]);

        try {
            $file = $request->file('excel_file');
            $sessionId = 'import_'.time().'_'.uniqid();
            
            // Lưu file tạm
            $tempDir = storage_path('app/imports');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            $tempFilePath = "{$tempDir}/{$sessionId}.xlsx";
            $file->move($tempDir, "{$sessionId}.xlsx");
            
            // Load spreadsheet để đếm số dòng
            $spreadsheet = IOFactory::load($tempFilePath);
            $sheet = $spreadsheet->getSheetByName('products');
            
            $totalRows = 0;
            if ($sheet) {
                $rows = $sheet->toArray();
                array_shift($rows); // Bỏ header
                
                $validRows = array_filter($rows, function($row) {
                    return !empty($row[0]); // Có SKU
                });
                $totalRows = count($validRows);
            } else {
                Log::warning('Import start: Sheet products không tồn tại', [
                    'available_sheets' => $spreadsheet->getSheetNames(),
                ]);
            }
            
            // Lưu thông tin import vào cache
            $cacheData = [
                'file_path' => $tempFilePath,
                'total_rows' => $totalRows,
                'processed' => 0,
                'status' => 'processing',
                'errors' => [],
                'created_at' => now()->toDateTimeString(),
            ];
            
            Cache::put("import_{$sessionId}", $cacheData, now()->addHours(2));

            return response()->json([
                'success' => true,
                'session_id' => $sessionId,
                'total_rows' => $totalRows,
                'message' => 'Bắt đầu nhập sản phẩm...',
            ]);

        } catch (\Exception $e) {
            Log::error('Import start error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi bắt đầu nhập: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Xử lý import chunk (được gọi nhiều lần)
     */
    public function processImportChunk(Request $request): JsonResponse
    {
        $sessionId = $request->input('session_id');
        $chunk = (int) $request->input('chunk');
        $chunkSize = (int) $request->input('chunk_size', 50);

        $request->validate([
            'session_id' => 'required|string',
            'chunk' => 'required|integer|min:0',
            'chunk_size' => 'required|integer|min:1|max:500',
        ]);

        $cacheKey = "import_{$sessionId}";
        $importData = Cache::get($cacheKey);

        if (! $importData) {
            return response()->json([
                'success' => false,
                'message' => 'Session không tồn tại hoặc đã hết hạn.',
            ], 404);
        }

        if ($importData['status'] === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Import đã bị hủy.',
                'cancelled' => true,
            ], 400);
        }

        if ($importData['status'] === 'completed') {
            return response()->json([
                'success' => true,
                'completed' => true,
                'processed' => $importData['processed'],
                'total' => $importData['total_rows'],
                'errors_count' => count($importData['errors'] ?? []),
            ]);
        }

        try {
            if (!file_exists($importData['file_path'])) {
                throw new \Exception('File import không tồn tại.');
            }

            $spreadsheet = IOFactory::load($importData['file_path']);
            $sheet = $spreadsheet->getSheetByName('products');
            
            if (!$sheet) {
                Log::error('Import chunk: Sheet products không tồn tại', [
                    'available_sheets' => $spreadsheet->getSheetNames(),
                ]);
                throw new \Exception('Sheet "products" không tồn tại!');
            }

            $rows = $sheet->toArray();
            $headers = array_shift($rows);
            
            // Lọc các dòng có SKU
            $validRows = array_filter($rows, function($row) {
                return !empty($row[0]); // Có SKU
            });
            $validRows = array_values($validRows); // Reindex

            // Tính toán chunk
            $startIndex = $chunk * $chunkSize;
            $endIndex = $startIndex + $chunkSize;
            $chunkRows = array_slice($validRows, $startIndex, $chunkSize);

            if (empty($chunkRows)) {
                
                // Hoàn thành import
                try {
                    $this->finalizeImport($sessionId, $importData);
                    
                    // Reload importData từ cache sau khi finalize
                    $finalImportData = Cache::get($cacheKey);
                    
                    return response()->json([
                        'success' => true,
                        'completed' => true,
                        'processed' => $finalImportData['processed'] ?? $importData['total_rows'],
                        'total' => $importData['total_rows'],
                        'errors_count' => count($finalImportData['errors'] ?? []),
                    ]);
                } catch (\Exception $e) {
                    Log::error('Import chunk: Lỗi khi finalize', [
                        'session_id' => $sessionId,
                        'error' => $e->getMessage(),
                    ]);
                    
                    // Cập nhật status lỗi
                    Cache::put($cacheKey, array_merge($importData, [
                        'status' => 'error',
                        'error' => $e->getMessage(),
                    ]), now()->addHours(2));
                    
                    return response()->json([
                        'success' => false,
                        'completed' => false,
                        'message' => 'Lỗi khi hoàn thành import: '.$e->getMessage(),
                        'processed' => $importData['processed'],
                        'total' => $importData['total_rows'],
                        'errors_count' => count($importData['errors'] ?? []),
                    ], 500);
                }
            }

            // Xử lý chunk này
            $errors = [];
            DB::beginTransaction();
            
            try {
                // Tạo spreadsheet tạm chỉ với chunk này
                $tempSpreadsheet = new Spreadsheet;
                $tempSheet = $tempSpreadsheet->getActiveSheet();
                $tempSheet->setTitle('products'); // QUAN TRỌNG: Set tên sheet
                $tempSheet->fromArray($headers, null, 'A1');
                $rowNum = 2;
                foreach ($chunkRows as $row) {
                    $tempSheet->fromArray($row, null, 'A'.$rowNum);
                    $rowNum++;
                }
                
                // Import chunk này - chỉ import products
                $this->importProducts($tempSpreadsheet, $errors);
                
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Import chunk error', [
                    'chunk' => $chunk,
                    'error' => $e->getMessage(),
                ]);
                
                $errors[] = [
                    'type' => 'CHUNK_ERROR',
                    'sku' => 'N/A',
                    'message' => $e->getMessage(),
                    'chunk' => $chunk,
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine(),
                ];
            }

            // Cập nhật progress
            $processed = $importData['processed'] + count($chunkRows);
            $allErrors = array_merge($importData['errors'] ?? [], $errors);
            
            Cache::put($cacheKey, array_merge($importData, [
                'processed' => $processed,
                'errors' => $allErrors,
                'last_chunk' => $chunk,
            ]), now()->addHours(2));

            $progress = ($processed / $importData['total_rows']) * 100;

            return response()->json([
                'success' => true,
                'processed' => $processed,
                'total' => $importData['total_rows'],
                'progress' => round($progress, 2),
                'errors_count' => count($allErrors),
                'completed' => false,
            ]);

        } catch (\Exception $e) {
            Log::error('Import chunk: Lỗi nghiêm trọng', [
                'session_id' => $sessionId,
                'chunk' => $chunk,
                'error' => $e->getMessage(),
            ]);

            Cache::put($cacheKey, array_merge($importData, [
                'status' => 'error',
                'error' => $e->getMessage(),
            ]), now()->addHours(2));

            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xử lý chunk: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Hủy import
     */
    public function cancelImport(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        $sessionId = $request->input('session_id');
        $cacheKey = "import_{$sessionId}";
        $importData = Cache::get($cacheKey);

        if ($importData) {
            Cache::put($cacheKey, array_merge($importData, [
                'status' => 'cancelled',
            ]), now()->addHours(2));

            // Xóa file tạm nếu có
            $this->cleanupImportFiles($sessionId);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã hủy nhập sản phẩm.',
        ]);
    }

    /**
     * Lấy progress của import
     */
    public function getImportProgress(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        $sessionId = $request->input('session_id');
        $cacheKey = "import_{$sessionId}";
        $importData = Cache::get($cacheKey);

        if (! $importData) {
            return response()->json([
                'success' => false,
                'message' => 'Session không tồn tại.',
            ], 404);
        }

        $progress = $importData['total_rows'] > 0
            ? ($importData['processed'] / $importData['total_rows']) * 100
            : 0;

        return response()->json([
            'success' => true,
            'processed' => $importData['processed'] ?? 0,
            'total' => $importData['total_rows'] ?? 0,
            'progress' => round($progress, 2),
            'status' => $importData['status'] ?? 'processing',
            'completed' => $importData['status'] === 'completed',
            'cancelled' => $importData['status'] === 'cancelled',
            'errors_count' => count($importData['errors'] ?? []),
        ]);
    }

    /**
     * Hoàn thành import
     */
    protected function finalizeImport(string $sessionId, array $importData)
    {
        $cacheKey = "import_{$sessionId}";
        
        // Kiểm tra xem đã finalize chưa
        $currentData = Cache::get($cacheKey);
        if ($currentData && $currentData['status'] === 'completed') {
            return;
        }

        // Đánh dấu đang finalize
        Cache::put($cacheKey, array_merge($importData, [
            'status' => 'finalizing',
        ]), now()->addHours(2));

        $errors = $importData['errors'] ?? [];

        try {
            // Import các sheet khác (images, faqs, how_tos, variants) từ file gốc
            if (file_exists($importData['file_path'])) {
                $spreadsheet = IOFactory::load($importData['file_path']);
                
                // Import Images (Sheet 2)
                try {
                    $this->importImages($spreadsheet, $errors);
                } catch (\Exception $e) {
                    Log::error('Finalize import: Lỗi khi import images', [
                        'error' => $e->getMessage(),
                    ]);
                    $errors[] = [
                        'type' => 'IMAGES_IMPORT_ERROR',
                        'sku' => 'N/A',
                        'message' => $e->getMessage(),
                    ];
                }

                // Import FAQs (Sheet 3)
                try {
                    $this->importFaqs($spreadsheet, $errors);
                } catch (\Exception $e) {
                    Log::error('Finalize import: Lỗi khi import FAQs', [
                        'error' => $e->getMessage(),
                    ]);
                    $errors[] = [
                        'type' => 'FAQS_IMPORT_ERROR',
                        'sku' => 'N/A',
                        'message' => $e->getMessage(),
                    ];
                }

                // Import How-Tos (Sheet 4)
                try {
                    $this->importHowTos($spreadsheet, $errors);
                } catch (\Exception $e) {
                    Log::error('Finalize import: Lỗi khi import How-Tos', [
                        'error' => $e->getMessage(),
                    ]);
                    $errors[] = [
                        'type' => 'HOWTOS_IMPORT_ERROR',
                        'sku' => 'N/A',
                        'message' => $e->getMessage(),
                    ];
                }

                // Import Variants (Sheet 5)
                try {
                    $this->importVariants($spreadsheet, $errors);
                } catch (\Exception $e) {
                    Log::error('Finalize import: Lỗi khi import Variants', [
                        'error' => $e->getMessage(),
                    ]);
                    $errors[] = [
                        'type' => 'VARIANTS_IMPORT_ERROR',
                        'sku' => 'N/A',
                        'message' => $e->getMessage(),
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('Finalize import error', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
            $errors[] = [
                'type' => 'FINALIZE_ERROR',
                'sku' => 'N/A',
                'message' => $e->getMessage(),
            ];
        }

        // Xóa cache tất cả sản phẩm
        $this->clearAllProductCaches();

        // Ghi log lỗi nếu có
        $logFile = null;
        if (!empty($errors)) {
            $logFile = $this->writeErrorLog($errors, "import_{$sessionId}.xlsx");
        }

        // Cập nhật status
        Cache::put($cacheKey, array_merge($importData, [
            'status' => 'completed',
            'completed_at' => now()->toDateTimeString(),
            'log_file' => $logFile,
            'errors' => $errors,
        ]), now()->addHours(2));

        // Xóa file tạm
        if (file_exists($importData['file_path'])) {
            @unlink($importData['file_path']);
        }
    }

    /**
     * Cleanup import files
     */
    protected function cleanupImportFiles(string $sessionId)
    {
        $importDir = storage_path('app/imports');
        $files = glob("{$importDir}/{$sessionId}*");
        foreach ($files as $file) {
            @unlink($file);
        }
    }
}
