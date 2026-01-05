<?php

namespace App\Services\Admin;

use App\Models\Image;
use App\Models\Product;
use App\Models\ProductFaq;
use App\Models\ProductHowTo;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\Tag;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as InterventionImage;

class ProductService
{
    public function create(array $data): Product
    {
        $product = DB::transaction(function () use ($data) {
            $payload = $this->extractProductPayload($data);
            $payload['category_ids'] = $this->resolveCategoryIds($data);

            $product = Product::create($payload);

            // Sync tags sau khi tạo product (cần product->id)
            $tagIds = Arr::get($data, 'tag_ids', []);
            $tagNames = Arr::get($data, 'tag_names');
            $this->syncTags($product, is_array($tagIds) ? $tagIds : [], $tagNames);

            // Sync images (tạo images và lưu IDs vào image_ids)
            $this->syncImages($product, Arr::get($data, 'images', []));

            // Sync FAQs
            $this->syncFaqs($product, Arr::get($data, 'faqs', []));

            // Sync How-Tos
            $this->syncHowTos($product, Arr::get($data, 'how_tos', []));

            // Sync Variants
            $this->syncVariants($product, Arr::get($data, 'variants', []));

            return $product->fresh();
        });

        // Sau khi đã lưu xong product và images, xử lý resize ảnh
        $this->processProductImages($product);

        return $product;
    }

    public function clearProductDetailCache(string $slug)
    {
        Cache::forget('product_detail_'.$slug);
    }

    public function update(Product $product, array $data): Product
    {
        $product = DB::transaction(function () use ($product, $data) {
            $payload = $this->extractProductPayload($data);
            $payload['category_ids'] = $this->resolveCategoryIds($data);

            $product->update($payload);

            // Sync tags (chỉ sync nếu có dữ liệu tags trong request và không rỗng)
            // Nếu không có tags trong request hoặc mảng rỗng, giữ nguyên tags cũ
            $tagIds = Arr::get($data, 'tag_ids', []);
            $tagNames = Arr::get($data, 'tag_names');

            // Chỉ sync nếu có ít nhất 1 tag ID hoặc tag name không rỗng
            $hasTagIds = is_array($tagIds) && ! empty($tagIds);
            $hasTagNames = ! empty($tagNames) && ! empty(trim($tagNames));

            // Lấy tags hiện tại của product để so sánh
            $currentTagIds = $product->tag_ids ?? [];
            $currentTagIds = is_array($currentTagIds) ? $currentTagIds : [];
            $currentTagIds = array_map('strval', array_values($currentTagIds)); // Convert to string and reindex
            $newTagIds = is_array($tagIds) ? array_map('strval', array_values($tagIds)) : [];

            // So sánh tag_ids: nếu giống nhau, không sync
            sort($currentTagIds);
            sort($newTagIds);
            $tagIdsChanged = $currentTagIds !== $newTagIds;

            // Chỉ sync nếu:
            // 1. Có tag_ids và tag_ids đã thay đổi, HOẶC
            // 2. Có tag_names (người dùng nhập tags mới)
            $shouldSync = ($hasTagIds && $tagIdsChanged) || ($hasTagNames);

            Log::info('syncTags check', [
                'product_id' => $product->id,
                'tag_ids' => $tagIds,
                'tag_names' => $tagNames,
                'hasTagIds' => $hasTagIds,
                'hasTagNames' => $hasTagNames,
                'currentTagIds' => $currentTagIds,
                'newTagIds' => $newTagIds,
                'tagIdsChanged' => $tagIdsChanged,
                'shouldSync' => $shouldSync,
            ]);

            if ($shouldSync) {
                $this->syncTags($product, $tagIds, $tagNames);
            }
            // Nếu không có tag_ids và tag_names hoặc rỗng, hoặc không có thay đổi, không sync (giữ nguyên tags cũ)

            // Sync images (chỉ sync nếu có dữ liệu images trong request và không rỗng)
            // Nếu không có images trong request hoặc mảng rỗng, giữ nguyên ảnh cũ
            if (isset($data['images']) && is_array($data['images']) && ! empty($data['images'])) {
                $this->syncImages($product, $data['images']);
            }

            // Sync FAQs
            $this->syncFaqs($product, Arr::get($data, 'faqs', []));

            // Sync How-Tos
            $this->syncHowTos($product, Arr::get($data, 'how_tos', []));

            // Sync Variants
            if (isset($data['variants']) && is_array($data['variants'])) {
                $this->syncVariants($product, $data['variants']);
            }

            $this->clearProductDetailCache($product->slug);

            return $product->fresh();
        });

        // Sau khi update xong, luôn xử lý lại ảnh (idempotent, sẽ ghi đè nếu đã tồn tại)
        $this->processProductImages($product);

        return $product;
    }

    public function delete(Product $product): void
    {
        DB::transaction(function () use ($product) {
            // 1. Xóa tags liên quan (Tag có entity_type = Product::class và entity_id = product->id)
            $tagsDeleted = Tag::where('entity_type', Product::class)
                ->where('entity_id', $product->id)
                ->delete();

            // 2. Xóa FAQs
            $faqsDeleted = ProductFaq::where('product_id', $product->id)->delete();

            // 3. Xóa How-Tos
            $howTosDeleted = ProductHowTo::where('product_id', $product->id)->delete();

            // 4. Xóa link images trong image_ids (không xóa Image records vì có thể được dùng bởi sản phẩm khác)
            $product->image_ids = null;

            // 5. Xóa editing lock
            $product->locked_by = null;
            $product->locked_at = null;

            // 6. Gắn category_id = 1 (danh mục mặc định) trước khi xóa mềm
            $defaultCategoryId = 1;
            if (! \App\Models\Category::where('id', $defaultCategoryId)->exists()) {
                // Nếu category id = 1 không tồn tại, tạo nó
                $defaultCategory = \App\Models\Category::firstOrCreate(
                    ['id' => $defaultCategoryId],
                    [
                        'name' => 'Danh mục mặc định',
                        'slug' => 'danh-muc-mac-dinh',
                        'is_active' => true,
                        'order' => 0,
                    ]
                );
            }

            $product->category_id = $defaultCategoryId;

            // 7. Xóa mềm: chuyển sản phẩm sang trạng thái tạm ẩn
            $product->is_active = false;

            // 8. Lưu tất cả thay đổi
            $product->save();

            // 8. Logging
            Log::info('Product deleted', [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'tags_deleted' => $tagsDeleted,
                'faqs_deleted' => $faqsDeleted,
                'how_tos_deleted' => $howTosDeleted,
                'deleted_by' => Auth::id(),
                'deleted_at' => now()->toDateTimeString(),
            ]);

            // 9. Clear cache
            $this->clearProductDetailCache($product->slug);
        });
    }

    private function extractProductPayload(array $data): array
    {
        $slug = Arr::get($data, 'slug');
        if (empty($slug)) {
            $slug = Str::slug($data['name'] ?? Str::random(6));
        }

        $domainName = Setting::where('key', 'site_url')->value('value') ?? config('app.url');
        $domainName = rtrim($domainName, '/');
        $canonicalUrl = $domainName.'/san-pham/'.$slug;

        // Normalize image URLs in description and short_description
        $description = $this->normalizeImageUrls(Arr::get($data, 'description'));
        $shortDescription = $this->normalizeImageUrls(Arr::get($data, 'short_description'));

        $includedCategoryIds = $this->normalizeIncludedCategories(Arr::get($data, 'category_included_ids', []));

        return [
            'sku' => Arr::get($data, 'sku'),
            'name' => Arr::get($data, 'name'),
            'slug' => $slug,
            'description' => $description,
            'short_description' => $shortDescription,
            'price' => Arr::get($data, 'price', 0),
            'sale_price' => Arr::get($data, 'sale_price'),
            'cost_price' => Arr::get($data, 'cost_price'),
            'stock_quantity' => Arr::get($data, 'stock_quantity', 0),
            'meta_title' => Arr::get($data, 'meta_title'),
            'meta_description' => Arr::get($data, 'meta_description'),
            'meta_keywords' => $this->normalizeMetaKeywords(Arr::get($data, 'meta_keywords')),
            // Luôn cập nhật meta_canonical theo slug và site_url để dữ liệu chính xác
            'meta_canonical' => $canonicalUrl,
            'primary_category_id' => Arr::get($data, 'primary_category_id'),
            'category_included_ids' => $includedCategoryIds,
            'is_featured' => Arr::get($data, 'is_featured', false),
            'created_by' => Arr::get($data, 'created_by', Auth::id()),
            'is_active' => Arr::get($data, 'is_active', true),
        ];
    }

    private function normalizeMetaKeywords($keywords): ?array
    {
        if (empty($keywords)) {
            return null;
        }

        if (is_array($keywords)) {
            return array_values(array_filter(array_map('trim', $keywords)));
        }

        if (is_string($keywords)) {
            $keywords = array_filter(array_map('trim', explode(',', $keywords)));

            return ! empty($keywords) ? array_values($keywords) : null;
        }

        return null;
    }

    private function resolveCategoryIds(array $data): ?array
    {
        $primary = Arr::get($data, 'primary_category_id');
        $extra = Arr::get($data, 'category_ids', []);

        $ids = array_filter(array_unique(array_merge(
            $extra,
            $primary ? [$primary] : []
        )));

        return ! empty($ids) ? $ids : null;
    }

    private function normalizeIncludedCategories($value): ?array
    {
        if (empty($value)) {
            return null;
        }

        if (! is_array($value)) {
            $value = [$value];
        }

        $ids = collect($value)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        return ! empty($ids) ? $ids : null;
    }

    /**
     * Sync tags cho product vào tags table với entity_type = 'App\Models\Product'
     */
    private function syncTags(Product $product, array $tagIds, ?string $tagNames = null): void
    {
        Log::info('syncTags called', [
            'product_id' => $product->id,
            'tagIds' => $tagIds,
            'tagNames' => $tagNames,
            'tagIds_empty' => empty($tagIds),
            'tagNames_empty' => empty($tagNames),
        ]);

        // Xóa tất cả tags cũ của product này
        Tag::where('entity_type', Product::class)
            ->where('entity_id', $product->id)
            ->delete();

        // Xử lý tag names từ input (tags mới)
        $allTagNames = [];
        if (! empty($tagNames)) {
            $newTagNames = $this->parseTagNames($tagNames);
            $allTagNames = array_merge($allTagNames, $newTagNames);
        }

        // Nếu không có tagIds và không có tagNames, xóa hết tags
        if (empty($tagIds) && empty($allTagNames)) {
            Log::info('syncTags: no tags, setting tag_ids to null', ['product_id' => $product->id]);
            $product->tag_ids = null;
            $product->saveQuietly();

            return;
        }

        // Lấy thông tin tags từ products (entity_type = Product::class)
        $existingTags = [];
        if (! empty($tagIds)) {
            $existingTags = Tag::whereIn('id', $tagIds)
                ->where('entity_type', Product::class)
                ->select('id', 'name', 'slug', 'description', 'is_active')
                ->get()
                ->unique('name')
                ->keyBy('id');

            // Lấy thêm tag names từ existing tags
            foreach ($existingTags as $tag) {
                $allTagNames[] = $tag->name;
            }
        }

        // Loại bỏ duplicate và tạo tags
        $allTagNames = array_unique(array_map('trim', $allTagNames));
        $createdTagIds = [];

        foreach ($allTagNames as $tagName) {
            if (empty($tagName)) {
                continue;
            }

            // Kiểm tra xem tag đã có với entity_id = product->id chưa
            $existingProductTag = Tag::where('entity_type', Product::class)
                ->where('entity_id', $product->id)
                ->where('name', $tagName)
                ->first();

            if ($existingProductTag) {
                // Nếu đã tồn tại, dùng tag đó
                $createdTagIds[] = $existingProductTag->id;

                continue;
            }

            // Tìm tag template (có thể từ products khác hoặc mới tạo)
            $templateTag = Tag::where('entity_type', Product::class)
                ->where('name', $tagName)
                ->first();

            // Tạo tag mới với entity_type và entity_id cho product này
            $baseSlug = Str::slug($tagName);
            $uniqueSlug = $baseSlug.'-product-'.$product->id;

            // Đảm bảo slug unique
            $counter = 1;
            while (Tag::where('slug', $uniqueSlug)->exists()) {
                $uniqueSlug = $baseSlug.'-product-'.$product->id.'-'.$counter;
                $counter++;
            }

            $newTag = Tag::create([
                'name' => $tagName,
                'slug' => $uniqueSlug,
                'description' => $templateTag->description ?? null,
                'is_active' => $templateTag->is_active ?? true,
                'usage_count' => 0,
                'entity_id' => $product->id,
                'entity_type' => Product::class,
            ]);
            $createdTagIds[] = $newTag->id;
        }

        // Cập nhật lại tag_ids trong products table
        $product->tag_ids = ! empty($createdTagIds) ? $createdTagIds : null;
        $product->saveQuietly();
    }

    /**
     * Parse tag names từ string (phân cách bằng dấu phẩy)
     */
    private function parseTagNames(string $tagNames): array
    {
        return array_filter(
            array_map('trim', explode(',', $tagNames)),
            fn ($name) => ! empty($name)
        );
    }

    /**
     * Sync images: tạo/update images và lưu IDs vào image_ids JSON của product
     */
    private function syncImages(Product $product, array $images): void
    {
        $keepIds = [];
        $hasPrimary = false;

        Log::info('syncImages called', [
            'product_id' => $product->id,
            'images_count' => count($images),
            'images' => $images,
        ]);

        foreach ($images as $order => $imageData) {
            // Bỏ qua nếu không có dữ liệu gì (không có id, existing_path, hoặc file)
            $hasId = ! empty(Arr::get($imageData, 'id'));
            $hasPath = ! empty(Arr::get($imageData, 'existing_path')) || ! empty(Arr::get($imageData, 'path'));
            $hasFile = isset($imageData['file']) && $imageData['file'] instanceof UploadedFile;

            if (! $hasId && ! $hasPath && ! $hasFile) {
                continue;
            }

            $imageId = Arr::get($imageData, 'id');
            $file = Arr::get($imageData, 'file');
            $path = Arr::get($imageData, 'existing_path', Arr::get($imageData, 'path'));
            // Lưu cả path (ví dụ: thumbs/filename.jpg), không chỉ basename
            $filename = $path ?: null;

            // Nếu có upload file mới, lưu file mới
            if ($file instanceof UploadedFile) {
                $filename = $this->storeImageFile($file);
            } elseif ($imageId) {
                // Nếu là ảnh cũ (có ID) và không có file mới
                $existingImage = Image::find($imageId);
                if ($existingImage) {
                    // Nếu có existing_path mới (chọn từ library), dùng path mới
                    if (! empty($path)) {
                        $filename = $path; // Lưu cả path, không chỉ basename
                        // Nếu path thay đổi, tìm xem ảnh mới đã tồn tại chưa
                        if ($filename !== $existingImage->url) {
                            $existingImageByUrl = Image::where('url', $filename)->first();
                            if ($existingImageByUrl) {
                                // Ảnh mới đã tồn tại, dùng lại ID của ảnh mới
                                $imageId = $existingImageByUrl->id;
                            }
                        }
                    } else {
                        $filename = $existingImage->url; // Lấy path từ database
                    }
                }
            } elseif (! empty($path)) {
                // Nếu có existing_path (chọn từ library) nhưng không có ID
                // Tìm xem ảnh này đã tồn tại trong database chưa
                $filename = $path; // Lưu cả path, không chỉ basename
                $existingImageByUrl = Image::where('url', $filename)->first();
                if ($existingImageByUrl) {
                    // Ảnh đã tồn tại, dùng lại ID
                    $imageId = $existingImageByUrl->id;
                }
            }

            // Nếu vẫn không có filename, bỏ qua (không tạo ảnh mới nếu không có file)
            if (empty($filename)) {
                // Nhưng nếu có imageId, vẫn giữ lại ảnh cũ
                if ($imageId) {
                    $existingImage = Image::find($imageId);
                    if ($existingImage) {
                        $keepIds[] = $existingImage->id;
                        if ($existingImage->is_primary) {
                            $hasPrimary = true;
                        }
                    }
                }

                continue;
            }

            $payload = [
                'url' => $filename,
                'title' => Arr::get($imageData, 'title'),
                'notes' => Arr::get($imageData, 'notes'),
                'alt' => Arr::get($imageData, 'alt'),
                'is_primary' => Arr::get($imageData, 'is_primary', false),
                'order' => Arr::get($imageData, 'order', $order),
            ];

            if ($imageId) {
                // Update existing image
                $image = Image::find($imageId);
                if ($image) {
                    // Xóa file cũ nếu thay đổi file (có upload file mới)
                    if ($file instanceof UploadedFile && $image->url && $image->url !== $filename) {
                        $this->deleteImageFile($image->url);
                    }
                    // Chỉ update nếu có thay đổi
                    $image->update($payload);
                    $keepIds[] = $image->id;
                    if ($payload['is_primary']) {
                        $hasPrimary = true;
                    }

                    continue;
                }
            }

            // Create new image (chỉ khi không có imageId)
            // Nếu filename đã tồn tại trong database, tìm và dùng lại
            $existingImageByUrl = Image::where('url', $filename)->first();
            if ($existingImageByUrl) {
                // Ảnh đã tồn tại, update metadata và dùng lại
                $existingImageByUrl->update($payload);
                $keepIds[] = $existingImageByUrl->id;
                if ($payload['is_primary']) {
                    $hasPrimary = true;
                }
            } else {
                // Tạo ảnh mới
                $image = Image::create($payload);
                $keepIds[] = $image->id;
                if ($payload['is_primary']) {
                    $hasPrimary = true;
                }
            }
        }

        // Đảm bảo có ít nhất 1 ảnh primary
        if (! $hasPrimary && ! empty($keepIds)) {
            Image::whereIn('id', $keepIds)
                ->orderBy('order')
                ->limit(1)
                ->update(['is_primary' => true]);
        }

        // Xóa các images không còn được sử dụng (nếu có trong image_ids cũ nhưng không có trong keepIds)
        $oldImageIds = $product->image_ids ?? [];
        if (! empty($oldImageIds)) {
            $obsoleteIds = array_diff($oldImageIds, $keepIds);
            if (! empty($obsoleteIds)) {
                foreach ($obsoleteIds as $obsoleteId) {
                    $img = Image::find($obsoleteId);
                    if ($img) {
                        $this->deleteImageFile($img->url);
                        $img->delete();
                    }
                }
            }
        }

        // Cập nhật image_ids trong product
        $product->image_ids = ! empty($keepIds) ? array_values($keepIds) : null;
        $product->saveQuietly();

        Log::info('syncImages completed', [
            'product_id' => $product->id,
            'keepIds' => $keepIds,
            'image_ids' => $product->image_ids,
        ]);

        // Refresh product để đảm bảo image_ids được cập nhật
        $product->refresh();
    }

    private function syncFaqs(Product $product, array $faqs): void
    {
        $keepIds = [];

        foreach ($faqs as $faq) {
            $faqId = Arr::get($faq, 'id');
            $question = Arr::get($faq, 'question');
            $answer = Arr::get($faq, 'answer');
            $order = Arr::get($faq, 'order', 0);

            if (empty($question)) {
                continue;
            }

            $payload = [
                'product_id' => $product->id,
                'question' => $question,
                'answer' => $answer ?: null,
                'order' => $order,
                'updated_at' => now(),
            ];

            if ($faqId && ProductFaq::where('product_id', $product->id)->where('id', $faqId)->exists()) {
                ProductFaq::where('id', $faqId)->update($payload);
                $keepIds[] = $faqId;
            } else {
                $newId = ProductFaq::create(array_merge($payload, [
                    'created_at' => now(),
                ]))->id;
                $keepIds[] = $newId;
            }
        }

        // Xóa FAQs không còn được sử dụng
        if (! empty($keepIds)) {
            ProductFaq::where('product_id', $product->id)
                ->whereNotIn('id', $keepIds)
                ->delete();
        } else {
            // Nếu không có FAQs nào, xóa tất cả
            ProductFaq::where('product_id', $product->id)->delete();
        }
    }

    private function syncHowTos(Product $product, array $howTos): void
    {
        $keepIds = [];

        foreach ($howTos as $howTo) {
            $howToId = Arr::get($howTo, 'id');
            $title = Arr::get($howTo, 'title');
            $description = Arr::get($howTo, 'description');
            $steps = $this->normalizeArrayField(Arr::get($howTo, 'steps'));
            $supplies = $this->normalizeArrayField(Arr::get($howTo, 'supplies'));
            $isActive = Arr::get($howTo, 'is_active', true);

            if (empty($title)) {
                continue;
            }

            $payload = [
                'product_id' => $product->id,
                'title' => $title,
                'description' => $description ?: null,
                'steps' => $steps,
                'supplies' => $supplies,
                'is_active' => $isActive,
                'updated_at' => now(),
            ];

            if ($howToId && ProductHowTo::where('product_id', $product->id)->where('id', $howToId)->exists()) {
                ProductHowTo::where('id', $howToId)->update($payload);
                $keepIds[] = $howToId;
            } else {
                $newId = ProductHowTo::create(array_merge($payload, [
                    'created_at' => now(),
                ]))->id;
                $keepIds[] = $newId;
            }
        }

        // Xóa How-Tos không còn được sử dụng
        if (! empty($keepIds)) {
            ProductHowTo::where('product_id', $product->id)
                ->whereNotIn('id', $keepIds)
                ->delete();
        } else {
            // Nếu không có How-Tos nào, xóa tất cả
            ProductHowTo::where('product_id', $product->id)->delete();
        }
    }

    private function syncVariants(Product $product, array $variants): void
    {
        $keepIds = [];

        foreach ($variants as $variant) {
            $variantId = Arr::get($variant, 'id');
            $name = trim(Arr::get($variant, 'name', ''));
            $sku = trim(Arr::get($variant, 'sku', ''));
            $price = (float) Arr::get($variant, 'price', 0);
            $salePrice = Arr::get($variant, 'sale_price');
            $costPrice = Arr::get($variant, 'cost_price');
            $stockQuantity = Arr::get($variant, 'stock_quantity');
            $imageId = Arr::get($variant, 'image_id');
            $isActive = Arr::get($variant, 'is_active', true);
            $sortOrder = (int) Arr::get($variant, 'sort_order', 0);

            // Xây dựng attributes từ các trường riêng lẻ
            $attributes = [];

            // Kích thước
            $size = trim(Arr::get($variant, 'size', ''));
            if (! empty($size)) {
                $attributes['size'] = $size;
            }

            // Có chậu - chỉ lưu nếu có giá trị hợp lệ (0 hoặc 1)
            $hasPot = Arr::get($variant, 'has_pot');
            if ($hasPot !== null && $hasPot !== '' && ($hasPot === '0' || $hasPot === '1' || $hasPot === 0 || $hasPot === 1)) {
                $attributes['has_pot'] = (bool) ($hasPot === '1' || $hasPot === 1);
            }

            // Loại combo
            $comboType = trim(Arr::get($variant, 'combo_type', ''));
            if (! empty($comboType)) {
                $attributes['combo_type'] = $comboType;
            }

            // Ghi chú
            $notes = trim(Arr::get($variant, 'notes', ''));
            if (! empty($notes)) {
                $attributes['notes'] = $notes;
            }

            // Nếu có attributes từ input trực tiếp (JSON), merge vào (ưu tiên direct attributes)
            $directAttributes = Arr::get($variant, 'attributes');
            if (is_array($directAttributes) && ! empty($directAttributes)) {
                $attributes = array_merge($attributes, $directAttributes);
            }

            // Bỏ qua nếu không có tên hoặc giá <= 0
            if (empty($name) || $price <= 0) {
                continue;
            }

            // Validate sale_price phải nhỏ hơn price
            if ($salePrice !== null && $salePrice !== '') {
                $salePrice = (float) $salePrice;
                if ($salePrice >= $price) {
                    $salePrice = null; // Bỏ sale_price nếu không hợp lệ
                }
            } else {
                $salePrice = null;
            }

            // Validate cost_price
            if ($costPrice !== null && $costPrice !== '') {
                $costPrice = (float) $costPrice;
            } else {
                $costPrice = null;
            }

            // Validate stock_quantity
            if ($stockQuantity !== null && $stockQuantity !== '') {
                $stockQuantity = max(0, (int) $stockQuantity);
            } else {
                $stockQuantity = null;
            }

            // Validate image_id
            if ($imageId && ! is_numeric($imageId)) {
                $imageId = null;
            }

            $payload = [
                'product_id' => $product->id,
                'name' => $name,
                'sku' => ! empty($sku) ? $sku : null,
                'price' => $price,
                'sale_price' => $salePrice,
                'cost_price' => $costPrice,
                'stock_quantity' => $stockQuantity,
                'image_id' => $imageId ? (int) $imageId : null,
                'attributes' => ! empty($attributes) ? $attributes : null,
                'is_active' => (bool) $isActive,
                'sort_order' => $sortOrder,
                'updated_at' => now(),
            ];

            // Debug: Log attributes để kiểm tra (chỉ log khi có attributes)
            if (! empty($attributes)) {
                Log::info('Variant attributes being saved', [
                    'variant_name' => $name,
                    'attributes' => $attributes,
                    'attributes_json' => json_encode($attributes),
                    'has_pot_raw' => Arr::get($variant, 'has_pot'),
                    'size_raw' => Arr::get($variant, 'size'),
                    'combo_type_raw' => Arr::get($variant, 'combo_type'),
                    'notes_raw' => Arr::get($variant, 'notes'),
                ]);
            }

            if ($variantId && ProductVariant::where('product_id', $product->id)->where('id', $variantId)->exists()) {
                ProductVariant::where('id', $variantId)->update($payload);
                $keepIds[] = $variantId;
            } else {
                $newId = ProductVariant::create(array_merge($payload, [
                    'created_at' => now(),
                ]))->id;
                $keepIds[] = $newId;
            }
        }

        // Xóa variants không còn được sử dụng
        if (! empty($keepIds)) {
            ProductVariant::where('product_id', $product->id)
                ->whereNotIn('id', $keepIds)
                ->delete();
        } else {
            // Nếu không có variants nào, xóa tất cả
            ProductVariant::where('product_id', $product->id)->delete();
        }
    }

    private function normalizeArrayField($value): ?array
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }

            return array_filter(array_map('trim', explode("\n", $value)));
        }

        if (is_array($value)) {
            return array_values(array_filter($value, function ($item) {
                return ! empty($item);
            }));
        }

        return null;
    }

    private function storeImageFile(UploadedFile $file): string
    {
        $destination = public_path('clients/assets/img/clothes');

        if (! is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        // Sử dụng tên gốc (đã chuẩn hóa) thay vì tạo tên random
        $originalName = $file->getClientOriginalName();
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();

        // Chuẩn hóa tên file để tránh unicode/khoảng trắng
        $safeBase = Str::slug($baseName) ?: 'image';
        $filename = $safeBase.'.'.$extension;

        // Nếu trùng tên thì tự tăng hậu tố
        $counter = 1;
        while (file_exists($destination.'/'.$filename)) {
            $filename = $safeBase.'-'.$counter.'.'.$extension;
            $counter++;
        }

        $file->move($destination, $filename);

        return $filename;
    }

    /**
     * Normalize image URLs in HTML content: convert relative URLs to absolute URLs
     * Format: site_url/clients/assets/img/clothes/filename.webp
     */
    private function normalizeImageUrls(?string $content): ?string
    {
        if (empty($content)) {
            return $content;
        }

        $siteUrl = Setting::where('key', 'site_url')->value('value') ?? config('app.url');
        $siteUrl = rtrim($siteUrl, '/');

        // Pattern to match image tags with relative URLs
        $pattern = '/<img([^>]*?)src=["\']([^"\']+)["\']/i';

        return preg_replace_callback($pattern, function ($matches) use ($siteUrl) {
            $attrs = $matches[1];
            $imageUrl = $matches[2];

            // If already absolute URL (starts with http:// or https://), keep it
            if (preg_match('/^https?:\/\//i', $imageUrl)) {
                return $matches[0];
            }

            // Extract filename from relative path
            // Handle patterns like: ../../clients/assets/img/clothes/filename.webp
            // or: clients/assets/img/clothes/filename.webp
            // or: /clients/assets/img/clothes/filename.webp
            $filename = null;
            $imagePath = null;

            // Remove relative path prefixes (../../, ../, ./)
            $cleanUrl = preg_replace('/^(\.\.\/)+/', '', $imageUrl);
            $cleanUrl = ltrim($cleanUrl, './');

            // Extract filename from clients/assets/img/clothes/ or clients/assets/img/other/
            if (preg_match('/clients\/assets\/img\/(clothes|other)\/([^\/"\']+\.(webp|jpg|jpeg|png|gif|svg))$/i', $cleanUrl, $fileMatches)) {
                $filename = $fileMatches[2];
                // Always use clothes directory
                $imagePath = 'clients/assets/img/clothes/'.$filename;
            } else {
                // Try to extract just the filename (last part after /)
                $filename = basename($cleanUrl);
                if (empty($filename) || ! preg_match('/\.(webp|jpg|jpeg|png|gif|svg)$/i', $filename)) {
                    // If can't extract valid filename, return original
                    return $matches[0];
                }
                $imagePath = 'clients/assets/img/clothes/'.$filename;
            }

            // Build absolute URL: site_url/clients/assets/img/clothes/filename
            $absoluteUrl = $siteUrl.'/'.$imagePath;

            return '<img'.$attrs.'src="'.$absoluteUrl.'"';
        }, $content);
    }

    private function deleteImageFile(?string $filename): void
    {
        if (! $filename) {
            return;
        }

        $path = public_path('clients/assets/img/clothes/'.$filename);
        if (file_exists($path)) {
            @unlink($path);
        }
    }

    /**
     * Xử lý resize ảnh sản phẩm sau khi create/update.
     *
     * - Ảnh chính: tạo 6 kích thước (400, 85, 230, 215, 175, 155) dạng WxH.
     * - Ảnh phụ: tạo 1 kích thước 85x85.
     * - Ảnh gốc giữ nguyên, không đổi tên, không đổi vị trí.
     * - Ảnh resize lưu tại: public/clients/assets/img/clothes/resize/{width}x{height}/
     *   với tên file GIỮ NGUYÊN tên gốc (baseName.extension, không thêm hậu tố kích thước).
     * - Ghi đè nếu file đã tồn tại (idempotent).
     */
    private function processProductImages(Product $product): void
    {
        try {
            $imageIds = $product->image_ids ?? [];
            if (empty($imageIds) || ! is_array($imageIds)) {
                return;
            }

            /** @var \Illuminate\Support\Collection<int,\App\Models\Image> $images */
            $images = Image::whereIn('id', $imageIds)
                ->orderBy('order')
                ->get();

            if ($images->isEmpty()) {
                return;
            }

            $primaryImage = $images->firstWhere('is_primary', true) ?? $images->first();
            if (! $primaryImage || ! $primaryImage->url) {
                return;
            }

            // Kích thước cho ảnh chính
            $mainSizes = [
                [500, 500],
                [150, 150],
                [300, 300]
            ];

            $this->generateResizedImagesForSingle($primaryImage->url, $mainSizes);

            // Ảnh phụ: tất cả ảnh còn lại
            $galleryImages = $images->filter(function (Image $image) use ($primaryImage) {
                return $image->id !== $primaryImage->id && ! empty($image->url);
            });

            if ($galleryImages->isEmpty()) {
                return;
            }

            $gallerySize = [[150, 150]];
            foreach ($galleryImages as $galleryImage) {
                $this->generateResizedImagesForSingle($galleryImage->url, $gallerySize);
            }
        } catch (\Throwable $e) {
            // Không được làm hỏng flow lưu sản phẩm nếu resize lỗi
            Log::error('processProductImages failed', [
                'product_id' => $product->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Tạo các bản resize cho một file ảnh gốc.
     *
     * @param  string  $relativePath  Đường dẫn tương đối lưu trong DB (ví dụ: "thumbs/cay-phat-tai.webp" hoặc "cay-phat-tai.webp")
     * @param  array<int,array{0:int,1:int}>  $sizes  Danh sách [width, height]
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
        if (! is_dir($resizeRoot)) {
            mkdir($resizeRoot, 0755, true);
        }

        $extension = pathinfo($originalPath, PATHINFO_EXTENSION) ?: 'webp';
        $baseName = pathinfo($originalPath, PATHINFO_FILENAME);

        foreach ($sizes as $size) {
            [$width, $height] = $size;

            if (! $width || ! $height) {
                continue;
            }

            // Mỗi kích thước nằm trong 1 folder riêng: resize/{width}x{height}/
            $sizeFolder = $width.'x'.$height;
            $resizeDir = $resizeRoot.DIRECTORY_SEPARATOR.$sizeFolder;
            if (! is_dir($resizeDir)) {
                mkdir($resizeDir, 0755, true);
            }

            // Tên file giữ nguyên như ảnh gốc: baseName.extension
            $targetFilename = $baseName.'.'.$extension;
            $targetPath = $resizeDir.DIRECTORY_SEPARATOR.$targetFilename;

            try {
                $image = InterventionImage::make($originalPath);

                // Lấy kích thước gốc
                $originalWidth = $image->width();
                $originalHeight = $image->height();

                // Tính tỷ lệ resize
                $ratio = min($width / $originalWidth, $height / $originalHeight);

                // Chỉ resize nếu ảnh gốc lớn hơn kích thước đích
                if ($originalWidth > $width || $originalHeight > $height) {
                    // Progressive resize: resize từng bước để giảm artifacts
                    // Đặc biệt hiệu quả khi downscale lớn (ví dụ: 4000px -> 85px)
                    $currentWidth = $originalWidth;
                    $currentHeight = $originalHeight;
                    $targetRatio = $ratio;

                    // Nếu tỷ lệ resize < 0.5 (giảm hơn 50%), resize từng bước
                    if ($targetRatio < 0.5) {
                        // Resize từng bước: giảm tối đa 50% mỗi lần
                        while ($currentWidth > $width * 1.1 || $currentHeight > $height * 1.1) {
                            $stepRatio = max(0.5, min($width / $currentWidth, $height / $currentHeight));
                            $newWidth = (int) ($currentWidth * $stepRatio);
                            $newHeight = (int) ($currentHeight * $stepRatio);

                            $image->resize($newWidth, $newHeight, function ($constraint) {
                                $constraint->aspectRatio();
                                $constraint->upsize();
                            });

                            $currentWidth = $newWidth;
                            $currentHeight = $newHeight;
                        }
                    }

                    // Resize cuối cùng về đúng kích thước đích với tỷ lệ khung hình
                    $image->resize($width, $height, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });

                    // Crop để đạt đúng kích thước nếu cần (ví dụ: 800x600)
                    $image->fit($width, $height, function ($constraint) {
                        $constraint->upsize();
                    });
                } else {
                    // Ảnh gốc nhỏ hơn hoặc bằng kích thước đích
                    // Chỉ crop nếu cần để đạt đúng tỷ lệ khung hình
                    if ($originalWidth !== $width || $originalHeight !== $height) {
                        $image->fit($width, $height, function ($constraint) {
                            $constraint->upsize();
                        });
                    }
                }

                // --- Sharpen thông minh theo kích thước ---
                // Ảnh nhỏ cần sharpen nhẹ hơn để tránh "lóa/gắt"
                $sharpen = match (true) {
                    $width <= 100 => 4,     // thumbnail rất nhỏ (85x85)
                    $width <= 200 => 6,     // thumbnail nhỏ (155x155)
                    $width <= 300 => 8,     // thumbnail trung bình
                    default => 10,          // ảnh lớn
                };
                $image->sharpen($sharpen);

                // --- Giảm halo cho thumbnail nhỏ ---
                // Blur vi mô và giảm gamma để triệt ánh sáng gắt
                if ($width <= 120) {
                    $image->blur(0.08);     // Đủ triệt halo, không làm mềm ảnh
                    $image->gamma(0.97);    // Giảm lóa rất nhẹ, giữ màu trung thực
                }

                // Xác định quality theo kích thước và extension
                // Ảnh nhỏ không cần quality quá cao → giảm dung lượng file
                // Ảnh lớn cần quality cao → giữ chi tiết tốt
                $baseQuality = match (true) {
                    $width <= 100 => 85,    // Thumbnail rất nhỏ: 85% (đủ nét, file nhỏ)
                    $width <= 200 => 88,    // Thumbnail nhỏ: 88%
                    $width <= 400 => 90,    // Ảnh trung bình: 90%
                    $width <= 800 => 92,    // Ảnh lớn: 92%
                    default => 95,          // Ảnh rất lớn: 95%
                };

                // Điều chỉnh theo định dạng file
                if (in_array(strtolower($extension), ['jpg', 'jpeg'])) {
                    $quality = $baseQuality;
                } elseif (strtolower($extension) === 'webp') {
                    // WebP có thể giữ chất lượng tốt với quality thấp hơn một chút
                    $quality = max(80, $baseQuality - 2);
                } elseif (strtolower($extension) === 'png') {
                    // PNG không có quality parameter, nhưng có thể optimize
                    $quality = null;
                } else {
                    $quality = $baseQuality;
                }

                // Lưu với quality cao để giữ chất lượng tốt nhất
                if ($quality !== null) {
                    $image->save($targetPath, $quality);
                } else {
                    $image->save($targetPath);
                }
            } catch (\Throwable $e) {
                Log::error('generateResizedImagesForSingle failed', [
                    'source' => $relativePath,
                    'width' => $width,
                    'height' => $height,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }
}
