<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Image;
use App\Models\Product;
use App\Models\ProductFaq;
use App\Models\ProductHowTo;
use App\Models\ProductVariant;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

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
     * File Excel gồm 4 sheets: products, images, faqs, how_tos
     */
    public function export()
    {
        $products = Product::with([
            'primaryCategory',
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
        $tagMap = Tag::pluck('name', 'id')->toArray();

        $spreadsheet = new Spreadsheet;

        // Sheet 1: Products
        $this->buildProductsSheet($spreadsheet, $products, $categoryMap, $tagMap, $images);

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
    private function buildProductsSheet(Spreadsheet $spreadsheet, $products, array $categoryMap, array $tagMap, $images)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('products');

        $headers = [
            'sku', 'name', 'slug', 'description', 'short_description',
            'price', 'sale_price', 'cost_price', 'stock_quantity',
            'meta_title', 'meta_description', 'meta_keywords',
            'meta_canonical', 'primary_category_slug', 'category_slugs', 'tag_slugs',
            'image_ids', 'is_featured', 'is_active', 'created_by',
        ];
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($products as $product) {
            $primarySlug = optional($product->primaryCategory)->slug;

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
                $categorySlugs,
                $tagNames,
                $imageIds,
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
            throw new \Exception('Sheet "products" không tồn tại!');
        }

        $rows = $sheet->toArray();
        $headers = array_shift($rows);

        $categoryMap = [];
        $tagCache = [];

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
            $categorySlugs = trim($row[14] ?? '');
            $tagSlugs = trim($row[15] ?? '');
            $imageIdsRaw = trim($row[16] ?? '');
            $isFeatured = isset($row[17]) ? (bool) $row[17] : false;
            $isActive = isset($row[18]) ? (bool) $row[18] : true;
            $createdBy = (int) ($row[19] ?? (Auth::check() ? Auth::id() : 1));

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
            $computedCanonical = $domainName.'/san-pham/'.$slug;

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
                'category_ids' => ! empty($categoryIds) ? $categoryIds : null,
                'tag_ids' => ! empty($tagIds) ? $tagIds : null,
                'is_featured' => $isFeatured,
                'is_active' => $isActive,
                'created_by' => $createdBy,
            ];

            if ($product) {
                // Lưu slug cũ để xóa cache
                $oldSlug = $product->slug;

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

                    // Xóa cache với slug cũ
                    Cache::forget('product_detail_'.$oldSlug);

                    // Nếu slug thay đổi, cũng xóa cache với slug mới
                    $newSlug = $product->fresh()->slug;
                    if ($newSlug !== $oldSlug) {
                        Cache::forget('product_detail_'.$newSlug);
                    }
                }
                // Nếu không có thay đổi → giữ nguyên cache
            } else {
                // Create: tạo mới với SKU
                $data['sku'] = $sku;
                $newProduct = Product::create($data);

                // Xóa cache với slug mới (tạo mới luôn cần xóa cache)
                Cache::forget('product_detail_'.$newProduct->slug);
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
                    $imageIdsRaw = trim($row[16] ?? '');

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
     * Xóa cache cho tất cả sản phẩm (product_detail_*, related_products_*, vouchers_for_product_*)
     * để đảm bảo dữ liệu luôn mới sau mỗi lần import Excel.
     */
    private function clearAllProductCaches(): void
    {
        Product::query()
            ->select('id', 'slug')
            ->chunkById(200, function ($products): void {
                foreach ($products as $product) {
                    Cache::forget('product_detail_'.$product->slug);
                    Cache::forget('related_products_'.$product->id);
                    Cache::forget('vouchers_for_product_'.$product->id);
                }
            });
    }
}
