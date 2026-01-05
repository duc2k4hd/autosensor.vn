<?php

namespace App\Services\Media;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ThumbnailService
{
    protected int $thumbnailWidth = 300;

    protected int $thumbnailHeight = 300;

    protected int $quality = 85;

    /**
     * Generate thumbnail for image
     */
    public function generateThumbnail(string $imagePath, string $thumbDirectory, string $filename): bool
    {
        if (! File::exists($imagePath)) {
            return false;
        }

        $extension = strtolower(File::extension($imagePath));
        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            return false;
        }

        // Create thumb directory if not exists
        if (! File::exists($thumbDirectory)) {
            File::makeDirectory($thumbDirectory, 0755, true);
        }

        $thumbPath = $thumbDirectory.'/'.$filename;

        try {
            // Create image resource based on type
            $image = match ($extension) {
                'jpg', 'jpeg' => imagecreatefromjpeg($imagePath),
                'png' => imagecreatefrompng($imagePath),
                'gif' => imagecreatefromgif($imagePath),
                'webp' => imagecreatefromwebp($imagePath),
                default => null,
            };

            if (! $image) {
                return false;
            }

            // Get original dimensions
            $originalWidth = imagesx($image);
            $originalHeight = imagesy($image);

            // Calculate thumbnail dimensions (maintain aspect ratio)
            $ratio = min($this->thumbnailWidth / $originalWidth, $this->thumbnailHeight / $originalHeight);
            $thumbWidth = (int) ($originalWidth * $ratio);
            $thumbHeight = (int) ($originalHeight * $ratio);

            // Create thumbnail
            $thumbnail = imagecreatetruecolor($thumbWidth, $thumbHeight);

            // Preserve transparency for PNG and GIF
            if ($extension === 'png' || $extension === 'gif') {
                imagealphablending($thumbnail, false);
                imagesavealpha($thumbnail, true);
                $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
                imagefilledrectangle($thumbnail, 0, 0, $thumbWidth, $thumbHeight, $transparent);
            }

            // Resize image
            imagecopyresampled(
                $thumbnail,
                $image,
                0, 0, 0, 0,
                $thumbWidth,
                $thumbHeight,
                $originalWidth,
                $originalHeight
            );

            // Save thumbnail as webp
            $thumbWebpPath = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $thumbPath);
            imagewebp($thumbnail, $thumbWebpPath, $this->quality);

            // Clean up
            imagedestroy($image);
            imagedestroy($thumbnail);

            return true;
        } catch (\Throwable $e) {
            Log::error('Thumbnail generation failed', [
                'image_path' => $imagePath,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get thumbnail path
     */
    public function getThumbnailPath(string $filePath): ?string
    {
        $directory = dirname($filePath);
        $filename = basename($filePath);
        $thumbPath = $directory.'/thumbs/'.preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $filename);

        return File::exists($thumbPath) ? $thumbPath : null;
    }

    /**
     * Delete thumbnail
     */
    public function deleteThumbnail(string $filePath): bool
    {
        $thumbPath = $this->getThumbnailPath($filePath);
        if ($thumbPath && File::exists($thumbPath)) {
            return File::delete($thumbPath);
        }

        return false;
    }
}
