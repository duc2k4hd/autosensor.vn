<?php

namespace App\Services\Admin;

use App\Helpers\CategoryHelper;
use App\Models\Category;
use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CategoryService
{
    protected string $imagePath = 'clients/assets/img/categories';

    /**
     * Create new category
     */
    public function create(array $data, ?UploadedFile $image = null): Category
    {
        // Normalize parent_id: empty string or 0 should be null
        if (isset($data['parent_id']) && ($data['parent_id'] === '' || $data['parent_id'] === 0)) {
            $data['parent_id'] = null;
        }

        // Generate unique slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = CategoryHelper::generateUniqueSlug($data['name']);
        } else {
            // Ensure slug is unique globally (not just within parent level)
            $data['slug'] = CategoryHelper::generateUniqueSlugGlobal($data['slug']);
        }

        // Handle image upload
        if ($image) {
            $data['image'] = $this->uploadImage($image, $data['slug']);
        }

        // Set default order if not provided
        if (! isset($data['order'])) {
            $maxOrder = Category::where('parent_id', $data['parent_id'] ?? null)
                ->max('order') ?? 0;
            $data['order'] = $maxOrder + 1;
        }

        // Set default is_active
        if (! isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        // Handle metadata - encode with unescaped Unicode
        $metadataJson = null;
        if (isset($data['metadata']) && is_array($data['metadata'])) {
            // Filter out null and empty values
            $filtered = array_filter($data['metadata'], function ($val) {
                return $val !== null && $val !== '';
            });

            // Always update meta_canonical based on current slug and site_url
            $siteUrl = rtrim(Setting::where('key', 'site_url')->value('value') ?? config('app.url'), '/');
            $finalSlug = $data['slug'] ?? CategoryHelper::generateUniqueSlug($data['name']);
            $filtered['meta_canonical'] = $siteUrl.'/'.$finalSlug;

            if (! empty($filtered)) {
                $metadataJson = json_encode($filtered, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            unset($data['metadata']);
        }

        $category = Category::create($data);

        // Set metadata directly to bypass cast
        if ($metadataJson !== null) {
            DB::table('categories')->where('id', $category->id)->update(['metadata' => $metadataJson]);
            $category->refresh();
        }

        Log::info('Category created', [
            'category_id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
        ]);

        return $category;
    }

    /**
     * Update category
     */
    public function update(Category $category, array $data, ?UploadedFile $image = null, bool $deleteOldImage = false): Category
    {
        // Normalize parent_id: empty string or 0 should be null
        if (isset($data['parent_id']) && ($data['parent_id'] === '' || $data['parent_id'] === 0)) {
            $data['parent_id'] = null;
        }

        // Check if parent is being changed
        if (isset($data['parent_id'])) {
            $newParentId = $data['parent_id'];
            $oldParentId = $category->parent_id;

            // Compare properly (handle null values)
            if ($newParentId != $oldParentId) {
                if (! CategoryHelper::canMoveToParent($category->id, $newParentId)) {
                    throw new \Exception('Không thể di chuyển danh mục thành con của chính nó hoặc con của nó.');
                }
            }
        }

        // Handle slug
        if (isset($data['slug']) && $data['slug'] !== $category->slug) {
            // Ensure slug is unique globally
            $data['slug'] = CategoryHelper::generateUniqueSlugGlobal($data['slug'], $category->id);
        } elseif (isset($data['name']) && $data['name'] !== $category->name && empty($data['slug'])) {
            // Auto-generate slug if name changed and slug not provided
            $data['slug'] = CategoryHelper::generateUniqueSlugGlobal(
                Str::slug($data['name']),
                $category->id
            );
        }

        // Handle image upload/delete
        if ($image) {
            // Delete old image if exists
            if ($category->image) {
                $this->deleteImage($category->image);
            }
            $data['image'] = $this->uploadImage($image, $data['slug'] ?? $category->slug);
        } elseif ($deleteOldImage && $category->image) {
            $this->deleteImage($category->image);
            $data['image'] = null;
        }

        // Protect default category (id = 1) - cannot change is_active
        if ($category->id === 1 && isset($data['is_active'])) {
            // Always keep default category active
            $data['is_active'] = true;
        }

        // Handle metadata - encode with unescaped Unicode
        $metadataJson = null;
        if (isset($data['metadata'])) {
            if (is_array($data['metadata'])) {
                // Filter out null and empty values
                $filtered = array_filter($data['metadata'], function ($val) {
                    return $val !== null && $val !== '';
                });

                // Always update meta_canonical based on current slug and site_url
                $siteUrl = rtrim(Setting::where('key', 'site_url')->value('value') ?? config('app.url'), '/');
                $finalSlug = $data['slug'] ?? $category->slug;
                $filtered['meta_canonical'] = $siteUrl.'/'.$finalSlug;

                if (! empty($filtered)) {
                    $metadataJson = json_encode($filtered, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } else {
                    $metadataJson = null;
                }
            } elseif (empty($data['metadata'])) {
                $metadataJson = null;
            }
            unset($data['metadata']);
        } else {
            // Even if metadata is not being updated, we should update meta_canonical if slug changed
            if (isset($data['slug']) && $data['slug'] !== $category->slug) {
                $currentMetadata = $category->metadata ?? [];
                if (is_string($currentMetadata)) {
                    $currentMetadata = json_decode($currentMetadata, true) ?? [];
                }

                $siteUrl = rtrim(Setting::where('key', 'site_url')->value('value') ?? config('app.url'), '/');
                $currentMetadata['meta_canonical'] = $siteUrl.'/'.$data['slug'];

                $metadataJson = json_encode($currentMetadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        $category->update($data);

        // Set metadata directly to bypass cast
        if (isset($metadataJson)) {
            DB::table('categories')->where('id', $category->id)->update(['metadata' => $metadataJson]);
            $category->refresh();
        }

        Log::info('Category updated', [
            'category_id' => $category->id,
            'name' => $category->name,
        ]);

        return $category->fresh();
    }

    /**
     * Delete category
     */
    public function delete(Category $category, bool $forceDeleteTree = false): bool
    {
        // Protect default category (id = 1)
        if ($category->id === 1) {
            throw new \Exception('Không thể xóa danh mục mặc định (ID: 1). Đây là danh mục hệ thống.');
        }

        // Check if category has children
        $childrenCount = $category->children()->count();
        if ($childrenCount > 0 && ! $forceDeleteTree) {
            throw new \Exception("Không thể xóa danh mục vì còn {$childrenCount} danh mục con.");
        }

        // Check if category is being used
        $productsCount = $category->primaryProducts()->count();
        if ($productsCount > 0) {
            throw new \Exception("Không thể xóa danh mục vì đang được sử dụng bởi {$productsCount} sản phẩm.");
        }

        $postsCount = $category->posts()->count();
        if ($postsCount > 0) {
            throw new \Exception("Không thể xóa danh mục vì đang được sử dụng bởi {$postsCount} bài viết.");
        }

        // Delete children if force delete tree
        if ($forceDeleteTree && $childrenCount > 0) {
            foreach ($category->children as $child) {
                $this->delete($child, true);
            }
        }

        // Delete image
        if ($category->image) {
            $this->deleteImage($category->image);
        }

        $categoryId = $category->id;
        $categoryName = $category->name;

        $category->delete();

        Log::info('Category deleted', [
            'category_id' => $categoryId,
            'name' => $categoryName,
            'force_delete_tree' => $forceDeleteTree,
        ]);

        return true;
    }

    /**
     * Reorder categories
     */
    public function reorder(array $orderData): bool
    {
        foreach ($orderData as $item) {
            Category::where('id', $item['id'])
                ->where('parent_id', $item['parent_id'] ?? null)
                ->update(['order' => $item['order']]);
        }

        Log::info('Categories reordered', [
            'count' => count($orderData),
        ]);

        return true;
    }

    /**
     * Upload category image
     */
    protected function uploadImage(UploadedFile $file, string $slug): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        // Convert to webp if image
        if (in_array($extension, ['jpg', 'jpeg', 'png']) && function_exists('imagewebp')) {
            $finalExtension = 'webp';
        } else {
            $finalExtension = $extension;
        }

        $filename = $slug.'-'.Str::random(8).'.'.$finalExtension;
        $destination = public_path($this->imagePath);

        if (! File::exists($destination)) {
            File::makeDirectory($destination, 0755, true);
        }

        $targetPath = $destination.DIRECTORY_SEPARATOR.$filename;

        // Convert to webp if needed
        if ($finalExtension === 'webp' && in_array($extension, ['jpg', 'jpeg', 'png'])) {
            $image = match ($extension) {
                'jpg', 'jpeg' => @imagecreatefromjpeg($file->getRealPath()),
                'png' => @imagecreatefrompng($file->getRealPath()),
                default => null,
            };

            if ($image) {
                // Check if image is palette-based (indexed color)
                // Palette images need to be converted to truecolor before WebP conversion
                if (imageistruecolor($image) === false) {
                    // Convert palette image to truecolor
                    $truecolorImage = imagecreatetruecolor(imagesx($image), imagesy($image));

                    // Preserve transparency for PNG
                    if ($extension === 'png') {
                        imagealphablending($truecolorImage, false);
                        imagesavealpha($truecolorImage, true);
                        $transparent = imagecolorallocatealpha($truecolorImage, 0, 0, 0, 127);
                        imagefill($truecolorImage, 0, 0, $transparent);
                    }

                    imagecopy($truecolorImage, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
                    imagedestroy($image);
                    $image = $truecolorImage;
                }

                // Try to convert to WebP
                $webpSuccess = @imagewebp($image, $targetPath, 90);
                imagedestroy($image);

                if (! $webpSuccess || ! file_exists($targetPath)) {
                    // Fallback: save as original format
                    $finalExtension = $extension;
                    $filename = $slug.'-'.Str::random(8).'.'.$finalExtension;
                    $targetPath = $destination.DIRECTORY_SEPARATOR.$filename;
                    $file->move($destination, $filename);
                }
            } else {
                // Fallback: move original
                $finalExtension = $extension;
                $filename = $slug.'-'.Str::random(8).'.'.$finalExtension;
                $targetPath = $destination.DIRECTORY_SEPARATOR.$filename;
                $file->move($destination, $filename);
            }
        } else {
            $file->move($destination, $filename);
        }

        // Generate thumbnail
        $this->generateThumbnail($targetPath, $destination, $filename);

        return $filename;
    }

    /**
     * Generate thumbnail for category image
     */
    protected function generateThumbnail(string $sourcePath, string $destination, string $filename): void
    {
        if (! function_exists('imagewebp')) {
            return;
        }

        $thumbDir = $destination.DIRECTORY_SEPARATOR.'thumbs';
        if (! File::exists($thumbDir)) {
            File::makeDirectory($thumbDir, 0755, true);
        }

        $thumbPath = $thumbDir.DIRECTORY_SEPARATOR.$filename;

        try {
            $image = @imagecreatefromwebp($sourcePath);
            if (! $image) {
                return;
            }

            $width = imagesx($image);
            $height = imagesy($image);
            $thumbWidth = 200;
            $thumbHeight = (int) ($height * ($thumbWidth / $width));

            $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            imagecopyresampled($thumb, $image, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);

            imagewebp($thumb, $thumbPath, 85);
            imagedestroy($image);
            imagedestroy($thumb);
        } catch (\Throwable $e) {
            Log::warning('Thumbnail generation failed', [
                'source' => $sourcePath,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Delete category image
     */
    protected function deleteImage(string $filename): void
    {
        $imagePath = public_path($this->imagePath.DIRECTORY_SEPARATOR.$filename);
        $thumbPath = public_path($this->imagePath.DIRECTORY_SEPARATOR.'thumbs'.DIRECTORY_SEPARATOR.$filename);

        if (File::exists($imagePath)) {
            File::delete($imagePath);
        }

        if (File::exists($thumbPath)) {
            File::delete($thumbPath);
        }
    }

    /**
     * Get category tree
     */
    public function getTree(bool $includeInactive = false): array
    {
        return CategoryHelper::buildTree(null, $includeInactive);
    }

    /**
     * Get category breadcrumb
     */
    public function getBreadcrumb(Category $category): array
    {
        return CategoryHelper::getBreadcrumb($category);
    }
}
