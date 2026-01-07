<?php

namespace App\Services\Admin;

use App\Models\Brand;
use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BrandService
{
    protected string $imagePath = 'clients/assets/img/brands';

    /**
     * Create new brand
     */
    public function create(array $data, ?UploadedFile $image = null): Brand
    {
        // Generate unique slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['name']);
        } else {
            $data['slug'] = $this->generateUniqueSlug($data['slug']);
        }

        // Handle image upload
        if ($image) {
            $data['image'] = $this->uploadImage($image, $data['slug']);
        }

        // Set default order if not provided
        if (! isset($data['order'])) {
            $maxOrder = Brand::max('order') ?? -1;
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
            $finalSlug = $data['slug'];
            $filtered['meta_canonical'] = $siteUrl.'/'.$finalSlug;

            if (! empty($filtered)) {
                $metadataJson = json_encode($filtered, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        } else {
            // Nếu không có metadata, tạo metadata mặc định với meta_canonical
            $siteUrl = rtrim(Setting::where('key', 'site_url')->value('value') ?? config('app.url'), '/');
            $finalSlug = $data['slug'];
            $metadataJson = json_encode([
                'meta_canonical' => $siteUrl.'/'.$finalSlug,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        // Remove metadata from data array before creating
        if (isset($data['metadata'])) {
            unset($data['metadata']);
        }

        $brand = Brand::create($data);

        // Set metadata directly to bypass cast
        if ($metadataJson !== null) {
            DB::table('brands')->where('id', $brand->id)->update(['metadata' => $metadataJson]);
            $brand->refresh();
        }

        Log::info('Brand created', [
            'brand_id' => $brand->id,
            'name' => $brand->name,
            'slug' => $brand->slug,
        ]);

        return $brand;
    }

    /**
     * Update brand
     */
    public function update(Brand $brand, array $data, ?UploadedFile $image = null): Brand
    {
        $oldSlug = $brand->slug;
        $oldIsActive = $brand->is_active;

        // Generate unique slug if changed
        if (isset($data['slug']) && $data['slug'] !== $brand->slug) {
            $data['slug'] = $this->generateUniqueSlug($data['slug'], $brand->id);
        } elseif (isset($data['name']) && $data['name'] !== $brand->name && empty($data['slug'])) {
            // Nếu name thay đổi nhưng không có slug mới, generate slug mới
            $data['slug'] = $this->generateUniqueSlug($data['name'], $brand->id);
        }

        // Handle image upload
        if ($image) {
            // Delete old image if exists
            if ($brand->image) {
                $this->deleteImage($brand->image);
            }
            $data['image'] = $this->uploadImage($image, $data['slug'] ?? $brand->slug);
        }

        // Handle metadata
        if (isset($data['metadata']) && is_array($data['metadata'])) {
            // Merge with existing metadata
            $currentMetadata = $brand->metadata ?? [];
            $newMetadata = array_merge($currentMetadata, $data['metadata']);

            // Filter out null and empty values
            $filtered = array_filter($newMetadata, function ($val) {
                return $val !== null && $val !== '';
            });

            // Always update meta_canonical based on current slug and site_url
            $siteUrl = rtrim(Setting::where('key', 'site_url')->value('value') ?? config('app.url'), '/');
            $finalSlug = $data['slug'] ?? $brand->slug;
            $filtered['meta_canonical'] = $siteUrl.'/'.$finalSlug;

            if (! empty($filtered)) {
                $metadataJson = json_encode($filtered, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                DB::table('brands')->where('id', $brand->id)->update(['metadata' => $metadataJson]);
            }

            unset($data['metadata']);
        } else {
            // Even if metadata is not being updated, we should update meta_canonical if slug changed
            if (isset($data['slug']) && $data['slug'] !== $oldSlug) {
                $currentMetadata = $brand->metadata ?? [];
                $siteUrl = rtrim(Setting::where('key', 'site_url')->value('value') ?? config('app.url'), '/');
                $currentMetadata['meta_canonical'] = $siteUrl.'/'.$data['slug'];
                $metadataJson = json_encode($currentMetadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                DB::table('brands')->where('id', $brand->id)->update(['metadata' => $metadataJson]);
            }
        }

        $brand->update($data);

        // Invalidate cache nếu slug hoặc is_active thay đổi
        if ($oldSlug !== $brand->slug) {
            Cache::forget('slug_type_'.$oldSlug);
            Cache::forget('slug_type_'.$brand->slug);
        } elseif (isset($data['is_active']) && $oldIsActive !== $brand->is_active) {
            // Nếu is_active thay đổi, invalidate cache
            Cache::forget('slug_type_'.$brand->slug);
        }

        Log::info('Brand updated', [
            'brand_id' => $brand->id,
            'name' => $brand->name,
            'slug' => $brand->slug,
        ]);

        return $brand->fresh();
    }

    /**
     * Delete brand
     */
    public function delete(Brand $brand): bool
    {
        // Check if brand is being used
        $productsCount = $brand->products()->count();
        if ($productsCount > 0) {
            throw new \Exception("Không thể xóa hãng vì đang được sử dụng bởi {$productsCount} sản phẩm.");
        }

        // Delete image
        if ($brand->image) {
            $this->deleteImage($brand->image);
        }

        $brandId = $brand->id;
        $brandName = $brand->name;
        $brandSlug = $brand->slug; // Lưu slug trước khi xóa

        $brand->delete();

        // Invalidate cache cho slug đã xóa
        if ($brandSlug) {
            Cache::forget('slug_type_'.$brandSlug);
        }

        Log::info('Brand deleted', [
            'brand_id' => $brandId,
            'name' => $brandName,
            'slug' => $brandSlug,
        ]);

        return true;
    }

    /**
     * Generate unique slug
     */
    protected function generateUniqueSlug(string $text, ?int $excludeId = null): string
    {
        $slug = Str::slug($text);
        $originalSlug = $slug;
        $counter = 1;

        while (true) {
            $query = Brand::where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
            if (! $query->exists()) {
                break;
            }
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Upload image and convert to webp if needed
     */
    protected function uploadImage(UploadedFile $file, string $slug): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $path = public_path($this->imagePath);

        if (! File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }

        // Nếu đã là webp, chỉ cần move file
        if ($extension === 'webp') {
            $filename = $slug.'-'.time().'.webp';
            $file->move($path, $filename);
            return $this->imagePath.'/'.$filename;
        }

        // Nếu là các định dạng khác (jpg, jpeg, png, gif), convert sang webp
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif']) && function_exists('imagewebp')) {
            // Tạo tên file webp
            $filename = $slug.'-'.time().'.webp';
            $targetPath = $path.'/'.$filename;

            // Load image từ file tạm
            $image = match ($extension) {
                'jpg', 'jpeg' => @imagecreatefromjpeg($file->getRealPath()),
                'png' => @imagecreatefrompng($file->getRealPath()),
                'gif' => @imagecreatefromgif($file->getRealPath()),
                default => null,
            };

            if ($image) {
                // Check if image is palette-based (indexed color)
                // Palette images need to be converted to truecolor before WebP conversion
                if (imageistruecolor($image) === false) {
                    // Convert palette image to truecolor
                    $truecolorImage = imagecreatetruecolor(imagesx($image), imagesy($image));
                    
                    // Preserve transparency for PNG and GIF
                    if (in_array($extension, ['png', 'gif'])) {
                        imagealphablending($truecolorImage, false);
                        imagesavealpha($truecolorImage, true);
                        $transparent = imagecolorallocatealpha($truecolorImage, 0, 0, 0, 127);
                        imagefill($truecolorImage, 0, 0, $transparent);
                    }
                    
                    imagecopy($truecolorImage, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
                    imagedestroy($image);
                    $image = $truecolorImage;
                } else {
                    // Đảm bảo preserve transparency cho truecolor images (PNG/GIF)
                    if (in_array($extension, ['png', 'gif'])) {
                        imagealphablending($image, false);
                        imagesavealpha($image, true);
                    }
                }

                // Convert to WebP với quality 90
                $webpSuccess = @imagewebp($image, $targetPath, 90);
                imagedestroy($image);

                if ($webpSuccess && File::exists($targetPath)) {
                    return $this->imagePath.'/'.$filename;
                }
            }
        }

        // Fallback: nếu không convert được, giữ nguyên định dạng gốc
        $filename = $slug.'-'.time().'.'.$extension;
        $file->move($path, $filename);
        return $this->imagePath.'/'.$filename;
    }

    /**
     * Delete image file
     */
    public function deleteImage(string $filename): void
    {
        $filePath = public_path($filename);
        if (File::exists($filePath)) {
            File::delete($filePath);
        }
    }
}

