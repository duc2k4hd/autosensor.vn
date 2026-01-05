<?php

namespace App\Services\Media;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class FileManager
{
    protected int $maxWidth = 2048;

    protected int $maxHeight = 2048;

    protected int $quality = 90;

    /**
     * Convert image to WebP format and resize if needed
     */
    public function convertToWebp(string $sourcePath, string $targetPath, ?int $maxDimension = null): bool
    {
        // Check if WebP is supported
        if (! function_exists('imagewebp')) {
            Log::error('WebP conversion: imagewebp() function not available. PHP GD extension may not support WebP.');

            return false;
        }

        if (! File::exists($sourcePath)) {
            Log::error('WebP conversion: Source file not found', ['source' => $sourcePath]);

            return false;
        }

        $extension = strtolower(File::extension($sourcePath));
        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            Log::error('WebP conversion: Unsupported file type', ['extension' => $extension]);

            return false;
        }

        // Ensure target directory exists
        $targetDir = dirname($targetPath);
        if (! File::exists($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        $maxDimension = $maxDimension ?? max($this->maxWidth, $this->maxHeight);

        try {
            // Create image resource
            $image = match ($extension) {
                'jpg', 'jpeg' => @imagecreatefromjpeg($sourcePath),
                'png' => @imagecreatefrompng($sourcePath),
                'gif' => @imagecreatefromgif($sourcePath),
                default => null,
            };

            if (! $image) {
                $error = error_get_last();
                Log::error('WebP conversion: Failed to create image resource', [
                    'source' => $sourcePath,
                    'extension' => $extension,
                    'error' => $error['message'] ?? 'Unknown error',
                ]);

                return false;
            }

            $originalWidth = imagesx($image);
            $originalHeight = imagesy($image);

            // Calculate new dimensions if image is too large
            $newWidth = $originalWidth;
            $newHeight = $originalHeight;

            if ($originalWidth > $maxDimension || $originalHeight > $maxDimension) {
                $ratio = min($maxDimension / $originalWidth, $maxDimension / $originalHeight);
                $newWidth = (int) ($originalWidth * $ratio);
                $newHeight = (int) ($originalHeight * $ratio);
            }

            // Create new image if resizing needed
            if ($newWidth !== $originalWidth || $newHeight !== $originalHeight) {
                $resized = imagecreatetruecolor($newWidth, $newHeight);

                // Preserve transparency for PNG
                if ($extension === 'png') {
                    imagealphablending($resized, false);
                    imagesavealpha($resized, true);
                    $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
                    imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
                }

                imagecopyresampled(
                    $resized,
                    $image,
                    0, 0, 0, 0,
                    $newWidth,
                    $newHeight,
                    $originalWidth,
                    $originalHeight
                );

                imagedestroy($image);
                $image = $resized;
            }

            // Save as WebP
            $result = @imagewebp($image, $targetPath, $this->quality);

            // Clean up
            imagedestroy($image);

            if ($result === false) {
                $error = error_get_last();
                Log::error('WebP conversion: Failed to save WebP file', [
                    'source' => $sourcePath,
                    'target' => $targetPath,
                    'error' => $error['message'] ?? 'Unknown error',
                    'writable' => is_writable($targetDir),
                ]);

                return false;
            }

            // Verify file was created
            if (! File::exists($targetPath)) {
                Log::error('WebP conversion: File not created after imagewebp()', [
                    'target' => $targetPath,
                ]);

                return false;
            }

            Log::debug('WebP conversion: Success', [
                'source' => $sourcePath,
                'target' => $targetPath,
                'size' => File::size($targetPath),
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('WebP conversion failed', [
                'source' => $sourcePath,
                'target' => $targetPath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Validate file type
     */
    public function isValidFileType(string $mimeType): bool
    {
        $allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'application/pdf',
            'video/mp4',
            'video/webm',
        ];

        return in_array($mimeType, $allowedTypes);
    }

    /**
     * Get file size in human readable format
     */
    public function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }
}
