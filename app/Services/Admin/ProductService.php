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

        // Invalidate cache cho slug mới
        if ($product->slug) {
            $this->clearProductDetailCache($product->slug);
        }

        return $product;
    }

    public function clearProductDetailCache(string $slug)
    {
        Cache::forget('product_detail_'.$slug);
        Cache::forget('slug_type_'.$slug); // Invalidate slug type cache
    }

    public function update(Product $product, array $data): Product
    {
        $oldSlug = $product->slug; // Lưu slug cũ để invalidate cache
        
        $product = DB::transaction(function () use ($product, $data, $oldSlug) {
            $payload = $this->extractProductPayload($data);
            $payload['category_ids'] = $this->resolveCategoryIds($data);

            $product->update($payload);
            
            // Nếu slug thay đổi, invalidate cache của slug cũ và slug mới
            if ($oldSlug !== $product->slug) {
                Cache::forget('slug_type_'.$oldSlug);
                Cache::forget('product_detail_'.$oldSlug);
                // Invalidate cache cho slug mới
                if ($product->slug) {
                    Cache::forget('slug_type_'.$product->slug);
                    Cache::forget('product_detail_'.$product->slug);
                }
            }
            
            // Invalidate cache khi is_active thay đổi
            if (isset($data['is_active']) && $product->isDirty('is_active')) {
                $slug = $product->slug ?? $oldSlug;
                if ($slug) {
                    Cache::forget('slug_type_'.$slug);
                    Cache::forget('product_detail_'.$slug);
                }
            }

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
            // Luôn sync để đảm bảo xóa các biến thể đã bị remove trên UI admin.
            $this->syncVariants($product, Arr::get($data, 'variants', []));

            // Invalidate cache sau khi update (bao gồm cả slug_type_ và product_detail_)
            $this->clearProductDetailCache($product->slug);
            
            // Nếu is_active thay đổi, cũng cần invalidate slug_type_ cache
            // (đã được xử lý trong clearProductDetailCache)

            return $product->fresh();
        });

        // Sau khi update xong, luôn xử lý lại ảnh (idempotent, sẽ ghi đè nếu đã tồn tại)
        $this->processProductImages($product);

        return $product;
    }

    public function delete(Product $product): void
    {
        $slug = $product->slug; // Lưu slug để invalidate cache sau khi xóa
        
        DB::transaction(function () use ($product) {
            // 1. Xóa tags liên quan (Tag có entity_type = Product::class và entity_id = product->id)
            $tagsDeleted = Tag::where('entity_type', Product::class)
                ->where('entity_id', $product->id)
                ->delete();

            // 2. Xóa FAQs
            $faqsDeleted = ProductFaq::where('product_id', $product->id)->delete();

            // 3. Xóa How-Tos
            $howTosDeleted = ProductHowTo::where('product_id', $product->id)->delete();

            // 4. Giữ nguyên image_ids để không mất ảnh khi xóa mềm

            // 5. Xóa editing lock
            $product->locked_by = null;
            $product->locked_at = null;

            // 6. Xóa mềm: chuyển sản phẩm sang trạng thái tạm ẩn
            $product->is_active = false;

            // 7. Lưu tất cả thay đổi
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

            // 9. Clear cache (trong transaction để đảm bảo consistency)
            $this->clearProductDetailCache($product->slug);
        });
        
        // Invalidate cache sau khi transaction commit (đảm bảo cache được clear ngay cả khi có lỗi)
        $this->clearProductDetailCache($slug);
    }

    private function extractProductPayload(array $data): array
    {
        $slug = Arr::get($data, 'slug');
        if (empty($slug)) {
            $slug = Str::slug($data['name'] ?? Str::random(6));
        }

        $domainName = Setting::where('key', 'site_url')->value('value') ?? config('app.url');
        $domainName = rtrim($domainName, '/');
        $canonicalUrl = $domainName.'/'.$slug;

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
            'brand_id' => Arr::get($data, 'brand_id'),
            'category_included_ids' => $includedCategoryIds,
            'link_catalog' => $this->normalizeLinkCatalog(Arr::get($data, 'link_catalog'), Arr::get($data, 'catalog_files', [])),
            'video_url' => Arr::get($data, 'video_url') ? trim(Arr::get($data, 'video_url')) : null,
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

            // Nếu có upload file mới, lưu file mới với tên theo SKU/tên sản phẩm
            if ($file instanceof UploadedFile) {
                $filename = $this->storeImageFile($file, $product, $order);
            } elseif ($imageId) {
                // Nếu là ảnh cũ (có ID) và không có file mới
                $existingImage = Image::find($imageId);
                if ($existingImage) {
                    // Nếu có existing_path mới (chọn từ library), đổi tên file nếu cần
                    if (! empty($path)) {
                        $oldPath = $existingImage->url;
                        $filename = $this->normalizeImageFileName($path, $product, $order, $oldPath);
                        // Nếu path thay đổi, tìm xem ảnh mới đã tồn tại chưa
                        if ($filename !== $existingImage->url) {
                            $existingImageByUrl = Image::where('url', $filename)->first();
                            if ($existingImageByUrl) {
                                // Ảnh mới đã tồn tại, dùng lại ID của ảnh mới
                                $imageId = $existingImageByUrl->id;
                            }
                        }
                    } else {
                        // Kiểm tra và đổi tên file cũ nếu tên không đúng
                        $filename = $this->normalizeImageFileName($existingImage->url, $product, $order, $existingImage->url);
                    }
                }
            } elseif (! empty($path)) {
                // Nếu có existing_path (chọn từ library) nhưng không có ID
                // Đổi tên file nếu cần và tìm xem ảnh này đã tồn tại trong database chưa
                $filename = $this->normalizeImageFileName($path, $product, $order, $path);
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

            // Đảm bảo url chỉ là tên file (basename), không có path
            $normalizedUrl = basename($filename);
            
            $payload = [
                'url' => $normalizedUrl,
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
            $isActive = Arr::get($variant, 'is_active', true);
            $sortOrder = (int) Arr::get($variant, 'sort_order', 0);
            $note = trim((string) (Arr::get($variant, 'note', Arr::get($variant, 'notes', ''))));

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

            $payload = [
                'product_id' => $product->id,
                'name' => $name,
                'sku' => ! empty($sku) ? $sku : null,
                'price' => $price,
                'sale_price' => $salePrice,
                'cost_price' => $costPrice,
                'stock_quantity' => $stockQuantity,
                'is_active' => (bool) $isActive,
                'sort_order' => $sortOrder,
                'note' => $note !== '' ? $note : null,
                'updated_at' => now(),
            ];

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

    /**
     * Lưu file ảnh với tên theo SKU hoặc tên sản phẩm
     * 
     * @param UploadedFile $file File ảnh cần lưu
     * @param Product $product Sản phẩm liên quan
     * @param int $order Thứ tự ảnh (0 = ảnh chính, >0 = ảnh phụ)
     * @return string Tên file đã lưu
     */
    private function storeImageFile(UploadedFile $file, Product $product, int $order = 0): string
    {
        $destination = public_path('clients/assets/img/clothes');

        if (! is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'webp');
        
        // Xác định base name: ưu tiên SKU, fallback về tên sản phẩm
        $baseName = null;
        
        // Nếu có SKU, dùng SKU (loại bỏ ký tự đặc biệt không hợp lệ cho tên file)
        if (!empty($product->sku)) {
            // Giữ nguyên SKU, chỉ loại bỏ ký tự không hợp lệ cho tên file
            $baseName = preg_replace('/[^a-zA-Z0-9\-_]/', '', $product->sku);
        }
        
        // Nếu không có SKU hoặc SKU rỗng sau khi clean, dùng tên sản phẩm
        if (empty($baseName) && !empty($product->name)) {
            // Chuyển tên sản phẩm thành slug (viết thường, cách nhau bằng dấu gạch ngang)
            $baseName = Str::slug($product->name);
        }
        
        // Fallback cuối cùng
        if (empty($baseName)) {
            $baseName = 'image';
        }
        
        // Nếu là ảnh phụ (order > 0), thêm số thứ tự -1, -2, -3, ...
        // Ảnh chính (order = 0): không có hậu tố
        // Ảnh phụ thứ 1 (order = 1): -1
        // Ảnh phụ thứ 2 (order = 2): -2
        // Ảnh phụ thứ 3 (order = 3): -3
        if ($order > 0) {
            $baseName = $baseName.'-'.$order;
        }
        
        $filename = $baseName.'.'.$extension;

        // Ghi đè nếu file đã tồn tại (không tạo hậu tố -1, -2, ...)
        if (file_exists($destination.'/'.$filename)) {
            // Xóa file cũ nếu có
            @unlink($destination.'/'.$filename);
        }

        $file->move($destination, $filename);

        return $filename;
    }

    /**
     * Normalize tên file ảnh theo SKU hoặc tên sản phẩm
     * Đổi tên file nếu tên hiện tại không đúng
     * 
     * @param string $currentPath Đường dẫn file hiện tại
     * @param Product $product Sản phẩm liên quan
     * @param int $order Thứ tự ảnh (0 = ảnh chính, >0 = ảnh phụ)
     * @param string|null $oldPath Đường dẫn file cũ (để xóa nếu đổi tên)
     * @return string Tên file đã normalize
     */
    private function normalizeImageFileName(string $currentPath, Product $product, int $order = 0, ?string $oldPath = null): string
    {
        // Normalize path: loại bỏ leading slash và prefix "clients/assets/img/clothes/" nếu có
        $normalizedPath = ltrim($currentPath, '/');
        $normalizedPath = preg_replace('#^clients/assets/img/clothes/#', '', $normalizedPath);
        
        // Lấy extension từ file hiện tại
        $extension = pathinfo($normalizedPath, PATHINFO_EXTENSION) ?: 'webp';
        
        // Xác định base name mong muốn: ưu tiên SKU, fallback về tên sản phẩm
        $desiredBaseName = null;
        
        // Nếu có SKU, dùng SKU (loại bỏ ký tự đặc biệt không hợp lệ cho tên file)
        if (!empty($product->sku)) {
            // Giữ nguyên SKU, chỉ loại bỏ ký tự không hợp lệ cho tên file
            $desiredBaseName = preg_replace('/[^a-zA-Z0-9\-_]/', '', $product->sku);
        }
        
        // Nếu không có SKU hoặc SKU rỗng sau khi clean, dùng tên sản phẩm
        if (empty($desiredBaseName) && !empty($product->name)) {
            // Chuyển tên sản phẩm thành slug (viết thường, cách nhau bằng dấu gạch ngang)
            $desiredBaseName = Str::slug($product->name);
        }
        
        // Fallback cuối cùng
        if (empty($desiredBaseName)) {
            $desiredBaseName = 'image';
        }
        
        // Nếu là ảnh phụ (order > 0), thêm số thứ tự -1, -2, -3, ...
        // Ảnh chính (order = 0): không có hậu tố
        // Ảnh phụ thứ 1 (order = 1): -1
        // Ảnh phụ thứ 2 (order = 2): -2
        // Ảnh phụ thứ 3 (order = 3): -3
        if ($order > 0) {
            $desiredBaseName = $desiredBaseName.'-'.$order;
        }
        
        $desiredFilename = $desiredBaseName.'.'.$extension;
        
        // Lấy tên file hiện tại (chỉ basename, không có path)
        $currentFilename = basename($normalizedPath);
        
        // Nếu tên file hiện tại đã đúng, trả về chỉ tên file (basename)
        if ($currentFilename === $desiredFilename) {
            return $currentFilename;
        }
        
        // Cần đổi tên file
        $destination = public_path('clients/assets/img/clothes');
        // Tìm file có thể ở nhiều vị trí: root hoặc trong subfolder
        $possiblePaths = [
            $destination.'/'.$normalizedPath,  // Path đầy đủ
            $destination.'/'.$currentFilename, // Chỉ tên file ở root
        ];
        
        $currentFullPath = null;
        foreach ($possiblePaths as $path) {
            if (is_file($path)) {
                $currentFullPath = $path;
                break;
            }
        }
        
        if (!$currentFullPath) {
            // File không tồn tại, trả về tên mong muốn (sẽ được tạo sau)
            return $desiredFilename;
        }
        
        $desiredFullPath = $destination.'/'.$desiredFilename;
        
        // Nếu file đích đã tồn tại và khác file nguồn, xóa file đích cũ
        if (is_file($desiredFullPath) && $currentFullPath !== $desiredFullPath) {
            @unlink($desiredFullPath);
        }
        
        // Đổi tên file
        if (rename($currentFullPath, $desiredFullPath)) {
            Log::info('normalizeImageFileName: file renamed', [
                'old_name' => $currentFilename,
                'new_name' => $desiredFilename,
                'product_id' => $product->id,
                'product_sku' => $product->sku,
            ]);
            return $desiredFilename; // Trả về chỉ tên file
        } else {
            // Nếu đổi tên thất bại, trả về tên file hiện tại (basename)
            Log::warning('normalizeImageFileName: failed to rename file', [
                'old_name' => $currentFilename,
                'new_name' => $desiredFilename,
                'product_id' => $product->id,
            ]);
            return $currentFilename; // Trả về chỉ tên file
        }
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

    /**
     * Normalize và xử lý link_catalog
     * Hỗ trợ cả upload files và links có sẵn
     */
    private function normalizeLinkCatalog($linkCatalog, array $catalogFiles = []): ?array
    {
        $catalogLinks = [];
        
        // Xử lý files được upload
        if (!empty($catalogFiles) && is_array($catalogFiles)) {
            foreach ($catalogFiles as $file) {
                if ($file instanceof UploadedFile) {
                    $savedPath = $this->storeCatalogFile($file);
                    if ($savedPath) {
                        $catalogLinks[] = $savedPath;
                    }
                }
            }
        }
        
        // Xử lý links có sẵn (từ input hoặc JSON)
        if (!empty($linkCatalog)) {
            if (is_string($linkCatalog)) {
                // Nếu là JSON string, decode
                $decoded = json_decode($linkCatalog, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $catalogLinks = array_merge($catalogLinks, $decoded);
                } else {
                    // Nếu là string thường, split bằng comma hoặc newline
                    $links = array_filter(array_map('trim', preg_split('/[,\n\r]+/', $linkCatalog)));
                    $catalogLinks = array_merge($catalogLinks, $links);
                }
            } elseif (is_array($linkCatalog)) {
                $catalogLinks = array_merge($catalogLinks, $linkCatalog);
            }
        }
        
        // Loại bỏ empty và duplicate, normalize paths
        $catalogLinks = array_values(array_unique(array_filter(array_map(function ($link) {
            $link = trim($link);
            if (empty($link)) {
                return null;
            }
            // Nếu là relative path, đảm bảo bắt đầu với clients/assets/catalog/
            if (!preg_match('/^https?:\/\//i', $link) && !str_starts_with($link, 'clients/assets/catalog/')) {
                // Nếu chỉ là filename, thêm path
                if (!str_contains($link, '/')) {
                    return 'clients/assets/catalog/'.$link;
                }
            }
            return $link;
        }, $catalogLinks))));
        
        return !empty($catalogLinks) ? $catalogLinks : null;
    }
    
    /**
     * Lưu catalog file vào public/clients/assets/catalog
     */
    private function storeCatalogFile(UploadedFile $file): ?string
    {
        $destination = public_path('clients/assets/catalog');
        
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        // Lấy tên file gốc và chuẩn hóa
        $originalName = $file->getClientOriginalName();
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        
        // Chuẩn hóa tên file
        $safeBase = Str::slug($baseName) ?: 'catalog';
        $filename = $safeBase.'.'.$extension;
        
        // Nếu trùng tên thì tự tăng hậu tố
        $counter = 1;
        while (file_exists($destination.'/'.$filename)) {
            $filename = $safeBase.'-'.$counter.'.'.$extension;
            $counter++;
        }
        
        try {
            $file->move($destination, $filename);
            return 'clients/assets/catalog/'.$filename;
        } catch (\Exception $e) {
            Log::error('Failed to store catalog file', [
                'filename' => $originalName,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
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
     * - Ảnh chính: tạo 6 kích thước (500, 150, 300) dạng WxH.
     * - Ảnh phụ: tạo 1 kích thước 150x150.
     * - Ảnh gốc giữ nguyên, không đổi tên, không đổi vị trí.
     * - Ảnh resize lưu tại: public/clients/assets/img/clothes/resize/{width}x{height}/
     *   với tên file GIỮ NGUYÊN tên gốc (baseName.extension, không thêm hậu tố kích thước).
     * - Ghi đè nếu file đã tồn tại (idempotent).
     */
    private function processProductImages(Product $product): void
    {
        try {
            Log::info('🔵🔵🔵 processProductImages: START', [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
            ]);
            
            $imageIds = $product->image_ids ?? [];
            Log::info('🟡 processProductImages: CHECKING IMAGE_IDS', [
                'product_id' => $product->id,
                'image_ids' => $imageIds,
                'image_ids_type' => gettype($imageIds),
                'image_ids_empty' => empty($imageIds),
                'image_ids_is_array' => is_array($imageIds),
                'image_ids_count' => is_array($imageIds) ? count($imageIds) : 0,
            ]);
            
            if (empty($imageIds) || ! is_array($imageIds)) {
                Log::warning('🔴 processProductImages: NO IMAGE_IDS', [
                    'product_id' => $product->id,
                    'image_ids' => $imageIds,
                ]);
                return;
            }

            /** @var \Illuminate\Support\Collection<int,\App\Models\Image> $images */
            $images = Image::whereIn('id', $imageIds)
                ->orderBy('order')
                ->get();

            Log::info('🟡 processProductImages: IMAGES LOADED FROM DB', [
                'product_id' => $product->id,
                'image_ids' => $imageIds,
                'images_count' => $images->count(),
                'images_data' => $images->map(function ($img) {
                    return [
                        'id' => $img->id,
                        'url' => $img->url,
                        'raw_url' => $img->getRawOriginal('url'),
                        'is_primary' => $img->is_primary,
                        'order' => $img->order,
                    ];
                })->toArray(),
            ]);

            if ($images->isEmpty()) {
                Log::warning('🔴 processProductImages: NO IMAGES FOUND IN DB', [
                    'product_id' => $product->id,
                    'image_ids' => $imageIds,
                ]);
                return;
            }

            $primaryImage = $images->firstWhere('is_primary', true) ?? $images->first();
            Log::info('🟡 processProductImages: PRIMARY IMAGE IDENTIFIED', [
                'product_id' => $product->id,
                'primary_image_id' => $primaryImage ? $primaryImage->id : null,
                'primary_image_url' => $primaryImage ? $primaryImage->url : null,
                'primary_image_raw_url' => $primaryImage ? $primaryImage->getRawOriginal('url') : null,
                'primary_image_is_primary' => $primaryImage ? $primaryImage->is_primary : null,
            ]);
            
            if (! $primaryImage || ! $primaryImage->url) {
                Log::error('🔴 processProductImages: NO PRIMARY IMAGE OR URL', [
                    'product_id' => $product->id,
                    'primary_image' => $primaryImage ? $primaryImage->id : null,
                    'primary_image_url' => $primaryImage ? $primaryImage->url : null,
                    'all_images' => $images->map(fn($img) => ['id' => $img->id, 'url' => $img->url, 'is_primary' => $img->is_primary])->toArray(),
                ]);
                return;
            }
            
            Log::info('🟢 processProductImages: PROCESSING IMAGES', [
                'product_id' => $product->id,
                'total_images' => $images->count(),
                'primary_image_url' => $primaryImage->url,
                'primary_image_raw_url' => $primaryImage->getRawOriginal('url'),
            ]);

            // Kích thước cho ảnh chính
            $mainSizes = [
                [500, 500],
                [150, 150],
                [300, 300]
            ];

            Log::info('🟡 processProductImages: CALLING generateResizedImagesForSingle FOR PRIMARY IMAGE', [
                'product_id' => $product->id,
                'primary_image_url' => $primaryImage->url,
                'primary_image_raw_url' => $primaryImage->getRawOriginal('url'),
                'main_sizes' => $mainSizes,
                'main_sizes_count' => count($mainSizes),
            ]);
            
            // Resize ảnh chính với tất cả sizes một lần, sẽ tự động thêm hậu tố -1, -2, -3
            $this->generateResizedImagesForSingle($primaryImage->url, $mainSizes, true);

            // Ảnh phụ: tất cả ảnh còn lại
            $galleryImages = $images->filter(function (Image $image) use ($primaryImage) {
                return $image->id !== $primaryImage->id && ! empty($image->url);
            });

            Log::info('🟡 processProductImages: GALLERY IMAGES FILTERED', [
                'product_id' => $product->id,
                'gallery_images_count' => $galleryImages->count(),
                'gallery_images' => $galleryImages->map(fn($img) => [
                    'id' => $img->id,
                    'url' => $img->url,
                    'raw_url' => $img->getRawOriginal('url'),
                ])->toArray(),
            ]);

            if ($galleryImages->isEmpty()) {
                Log::info('🟡 processProductImages: NO GALLERY IMAGES', [
                    'product_id' => $product->id,
                ]);
            } else {
                $gallerySize = [[150, 150]];
                foreach ($galleryImages as $galleryImage) {
                    Log::info('🟡 processProductImages: CALLING generateResizedImagesForSingle FOR GALLERY IMAGE', [
                        'product_id' => $product->id,
                        'gallery_image_id' => $galleryImage->id,
                        'gallery_image_url' => $galleryImage->url,
                        'gallery_image_raw_url' => $galleryImage->getRawOriginal('url'),
                        'gallery_size' => $gallerySize,
                    ]);
                    $this->generateResizedImagesForSingle($galleryImage->url, $gallerySize);
                }
            }
            
            Log::info('✅✅✅ processProductImages: COMPLETED', [
                'product_id' => $product->id,
                'total_images_processed' => 1 + $galleryImages->count(),
            ]);
        } catch (\Throwable $e) {
            // Không được làm hỏng flow lưu sản phẩm nếu resize lỗi
            Log::error('🔴🔴🔴 processProductImages: EXCEPTION', [
                'product_id' => $product->id,
                'exception_class' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Tạo các bản resize cho một file ảnh gốc.
     *
     * @param  string  $relativePath  Đường dẫn tương đối lưu trong DB (ví dụ: "thumbs/cay-phat-tai.webp" hoặc "cay-phat-tai.webp")
     * @param  array<int,array{0:int,1:int}>  $sizes  Danh sách [width, height]
     * @param  bool  $isPrimary  Có phải ảnh chính không (nếu true, sẽ thêm hậu tố -1, -2, -3)
     */
    private function generateResizedImagesForSingle(string $relativePath, array $sizes, bool $isPrimary = false): void
    {
        Log::info('🔵 generateResizedImagesForSingle: START', [
            'relative_path' => $relativePath,
            'sizes' => $sizes,
            'is_primary' => $isPrimary,
            'sizes_count' => count($sizes),
        ]);

        if (empty($sizes)) {
            Log::warning('🔴 generateResizedImagesForSingle: NO SIZES PROVIDED', [
                'relative_path' => $relativePath,
            ]);
            return;
        }
        
        if ($relativePath === '' || $relativePath === null) {
            Log::warning('🔴 generateResizedImagesForSingle: EMPTY RELATIVE PATH');
            return;
        }

        // Normalize path: loại bỏ leading slash và prefix "clients/assets/img/clothes/" nếu có
        $normalizedPath = ltrim($relativePath, '/');
        $normalizedPath = preg_replace('#^clients/assets/img/clothes/#', '', $normalizedPath);
        // Loại bỏ subfolder nếu có (chỉ giữ filename)
        $normalizedPath = basename($normalizedPath);
        
        Log::info('🟡 generateResizedImagesForSingle: PATH NORMALIZED', [
            'original_path' => $relativePath,
            'normalized_path' => $normalizedPath,
            'basename' => basename($normalizedPath),
        ]);
        
        // Nếu path rỗng sau khi normalize, bỏ qua
        if ($normalizedPath === '' || $normalizedPath === null) {
            Log::warning('🔴 generateResizedImagesForSingle: NORMALIZED PATH IS EMPTY', [
                'original_path' => $relativePath,
            ]);
            return;
        }

        $originalPath = public_path('clients/assets/img/clothes/'.$normalizedPath);
        $clothesDir = public_path('clients/assets/img/clothes');
        
        Log::info('🟡 generateResizedImagesForSingle: CHECKING SOURCE FILE', [
            'normalized_path' => $normalizedPath,
            'clothes_dir' => $clothesDir,
            'clothes_dir_exists' => is_dir($clothesDir),
            'clothes_dir_writable' => is_dir($clothesDir) ? is_writable($clothesDir) : false,
            'full_path' => $originalPath,
            'file_exists' => is_file($originalPath),
            'file_readable' => is_file($originalPath) ? is_readable($originalPath) : false,
            'file_size' => is_file($originalPath) ? filesize($originalPath) : 0,
        ]);
        
        // Thử tìm file trong các vị trí có thể
        if (! is_file($originalPath)) {
            // Thử tìm trong subfolder
            $possiblePaths = [
                $originalPath,
                public_path('clients/assets/img/clothes/thumbs/'.$normalizedPath),
                public_path('clients/assets/img/clothes/'.$normalizedPath),
            ];
            
            $foundPath = null;
            foreach ($possiblePaths as $possiblePath) {
                if (is_file($possiblePath)) {
                    $foundPath = $possiblePath;
                    break;
                }
            }
            
            if ($foundPath) {
                $originalPath = $foundPath;
                Log::info('🟢 generateResizedImagesForSingle: FOUND FILE IN SUBFOLDER', [
                    'found_path' => $foundPath,
                ]);
            } else {
                Log::error('🔴 generateResizedImagesForSingle: SOURCE FILE NOT FOUND', [
                    'normalized_path' => $normalizedPath,
                    'searched_paths' => $possiblePaths,
                    'original_path' => $relativePath,
                    'clothes_dir_contents' => is_dir($clothesDir) ? array_slice(scandir($clothesDir), 0, 20) : [],
                ]);
                return;
            }
        }

        $resizeRoot = public_path('clients/assets/img/clothes/resize');
        $resizeRootCreated = false;
        if (! is_dir($resizeRoot)) {
            $resizeRootCreated = mkdir($resizeRoot, 0755, true);
            Log::info('🟡 generateResizedImagesForSingle: CREATED RESIZE ROOT', [
                'resize_root' => $resizeRoot,
                'created' => $resizeRootCreated,
                'exists_now' => is_dir($resizeRoot),
                'writable' => is_dir($resizeRoot) ? is_writable($resizeRoot) : false,
            ]);
        } else {
            Log::info('🟢 generateResizedImagesForSingle: RESIZE ROOT EXISTS', [
                'resize_root' => $resizeRoot,
                'writable' => is_writable($resizeRoot),
            ]);
        }

        $extension = pathinfo($originalPath, PATHINFO_EXTENSION) ?: 'webp';
        $baseName = pathinfo($originalPath, PATHINFO_FILENAME);
        
        Log::info('🟡 generateResizedImagesForSingle: FILE INFO EXTRACTED', [
            'extension' => $extension,
            'base_name' => $baseName,
            'original_width' => null, // Sẽ lấy sau
            'original_height' => null, // Sẽ lấy sau
        ]);

        foreach ($sizes as $index => $size) {
            [$width, $height] = $size;

            if (! $width || ! $height) {
                Log::warning('generateResizedImagesForSingle: width/height empty, skip', [
                    'relative_path' => $relativePath,
                    'size' => $size,
                ]);
                continue;
            }

            // Mỗi kích thước nằm trong 1 folder riêng: resize/{width}x{height}/
            $sizeFolder = $width.'x'.$height;
            $resizeDir = $resizeRoot.DIRECTORY_SEPARATOR.$sizeFolder;
            if (! is_dir($resizeDir)) {
                mkdir($resizeDir, 0755, true);
            }

            // Tên file resize phải giống hệt tên gốc, chỉ khác thư mục (theo yêu cầu)
            // Ví dụ: /clothes/E3Z-T61.jpg -> /clothes/resize/500x500/E3Z-T61.jpg
            $targetFilename = $baseName.'.'.$extension;
            $targetPath = $resizeDir.DIRECTORY_SEPARATOR.$targetFilename;
            
            // Ghi đè file cũ nếu đã tồn tại (đảm bảo resize lại khi update)
            if (is_file($targetPath)) {
                @unlink($targetPath);
            }

            try {
                Log::info('🟡 generateResizedImagesForSingle: PROCESSING SIZE', [
                    'index' => $index,
                    'width' => $width,
                    'height' => $height,
                    'is_primary' => $isPrimary,
                    'target_filename' => $targetFilename,
                    'target_path' => $targetPath,
                    'target_dir' => $resizeDir,
                    'target_dir_exists' => is_dir($resizeDir),
                    'target_dir_writable' => is_dir($resizeDir) ? is_writable($resizeDir) : false,
                    'target_file_exists' => is_file($targetPath),
                ]);
                
                // Intervention Image v3: sử dụng ImageManager thay vì ImageManagerStatic
                if (! class_exists('\\Intervention\\Image\\ImageManager')) {
                    Log::error('🔴 generateResizedImagesForSingle: INTERVENTION IMAGE LIBRARY NOT FOUND');
                    continue;
                }

                if (! class_exists('\\Intervention\\Image\\Drivers\\Gd\\Driver')) {
                    Log::error('🔴 generateResizedImagesForSingle: GD DRIVER NOT FOUND');
                    continue;
                }

                // Kiểm tra GD extension
                if (! extension_loaded('gd')) {
                    Log::error('🔴 generateResizedImagesForSingle: GD EXTENSION NOT LOADED');
                    continue;
                }

                Log::info('🟢 generateResizedImagesForSingle: READING SOURCE IMAGE', [
                    'source_path' => $originalPath,
                    'source_exists' => is_file($originalPath),
                    'source_size' => is_file($originalPath) ? filesize($originalPath) : 0,
                ]);
                
                // Intervention Image v3: tạo ImageManager với driver và sử dụng read()
                $manager = new \Intervention\Image\ImageManager(
                    new \Intervention\Image\Drivers\Gd\Driver()
                );
                
                $image = $manager->read($originalPath);

                // Lấy kích thước gốc
                $originalWidth = $image->width();
                $originalHeight = $image->height();
                
                Log::info('🟢 generateResizedImagesForSingle: IMAGE LOADED', [
                    'original_width' => $originalWidth,
                    'original_height' => $originalHeight,
                    'target_width' => $width,
                    'target_height' => $height,
                ]);

                // Intervention Image v3: resize tự động giữ aspect ratio
                // Sử dụng cover() để crop và resize về đúng kích thước
                // cover() sẽ resize và crop để đạt đúng width x height
                $image->cover($width, $height);

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
                // Intervention Image v3: blur() nhận int (0-100), không phải float
                if ($width <= 120) {
                    $image->blur(1);        // Blur nhẹ (1/100)
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
                // Intervention Image v3: truyền quality qua named parameter
                if (in_array(strtolower($extension), ['jpg', 'jpeg'])) {
                    $quality = $baseQuality;
                } elseif (strtolower($extension) === 'webp') {
                    // WebP có thể giữ chất lượng tốt với quality thấp hơn một chút
                    $quality = max(80, $baseQuality - 2);
                } elseif (strtolower($extension) === 'png') {
                    // PNG không có quality parameter
                    $quality = null;
                } else {
                    $quality = $baseQuality;
                }

                // Lưu với quality cao để giữ chất lượng tốt nhất
                // Intervention Image v3: save() tự động encode theo extension, truyền quality qua options
                Log::info('🟡 generateResizedImagesForSingle: SAVING RESIZED IMAGE', [
                    'target_path' => $targetPath,
                    'target_dir' => dirname($targetPath),
                    'target_dir_exists' => is_dir(dirname($targetPath)),
                    'target_dir_writable' => is_dir(dirname($targetPath)) ? is_writable(dirname($targetPath)) : false,
                    'quality' => $quality,
                    'width' => $width,
                    'height' => $height,
                    'extension' => $extension,
                    'sharpen' => $sharpen,
                ]);
                
                // Đảm bảo thư mục tồn tại và có quyền ghi
                if (! is_dir(dirname($targetPath))) {
                    $dirCreated = mkdir(dirname($targetPath), 0755, true);
                    Log::info('🟡 generateResizedImagesForSingle: CREATED TARGET DIR', [
                        'dir' => dirname($targetPath),
                        'created' => $dirCreated,
                    ]);
                }
                
                $saveStartTime = microtime(true);
                if ($quality !== null) {
                    // Truyền quality qua named parameter
                    $saved = $image->save($targetPath, quality: $quality);
                } else {
                    $saved = $image->save($targetPath);
                }
                $saveEndTime = microtime(true);
                $saveDuration = round(($saveEndTime - $saveStartTime) * 1000, 2);
                
                // Kiểm tra file đã được lưu chưa
                $fileExists = is_file($targetPath);
                $fileSize = $fileExists ? filesize($targetPath) : 0;
                $fileReadable = $fileExists ? is_readable($targetPath) : false;
                
                Log::info('🟢 generateResizedImagesForSingle: SAVE COMPLETED', [
                    'source' => $normalizedPath,
                    'target' => $targetPath,
                    'size' => $width.'x'.$height,
                    'saved_result' => $saved,
                    'file_exists' => $fileExists,
                    'file_readable' => $fileReadable,
                    'file_size' => $fileSize,
                    'save_duration_ms' => $saveDuration,
                ]);

                if (! $fileExists || $fileSize === 0) {
                    Log::error('🔴 generateResizedImagesForSingle: OUTPUT FILE MISSING OR EMPTY', [
                        'target_path' => $targetPath,
                        'target_dir' => dirname($targetPath),
                        'target_dir_exists' => is_dir(dirname($targetPath)),
                        'target_dir_writable' => is_dir(dirname($targetPath)) ? is_writable(dirname($targetPath)) : false,
                        'width' => $width,
                        'height' => $height,
                        'source' => $originalPath,
                        'source_exists' => is_file($originalPath),
                        'extension' => $extension,
                        'quality' => $quality,
                        'saved_result' => $saved,
                        'php_error' => error_get_last(),
                    ]);
                } else {
                    Log::info('✅ generateResizedImagesForSingle: SUCCESS', [
                        'size' => $width.'x'.$height,
                        'target_file' => $targetFilename,
                        'file_size' => $fileSize,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('🔴 generateResizedImagesForSingle: EXCEPTION', [
                    'source' => $normalizedPath,
                    'original_path' => $relativePath,
                    'width' => $width,
                    'height' => $height,
                    'target_path' => $targetPath ?? null,
                    'exception_class' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'gd_info' => function_exists('gd_info') ? gd_info() : 'GD not available',
                ]);
            }
        }
        
        Log::info('🔵 generateResizedImagesForSingle: END', [
            'relative_path' => $relativePath,
            'sizes_processed' => count($sizes),
        ]);
    }
}
