<?php

namespace App\Helpers;

class ImageOptimizer
{
    /**
     * Compress an image file so that its stored disk size is <= $targetKB (default 100KB).
     *
     * @param string $sourcePath Path to source image file on server
     * @param string|null $destinationPath Destination path (if null, overwrites $sourcePath)
     * @param int $targetKB Target maximum file size in KB (default 100)
     * @param int $maxDimension Max width/height dimension in pixels (default 1200)
     * @return bool True if compression was performed or file was already compliant
     */
    public static function compressToTargetSize(string $sourcePath, ?string $destinationPath = null, int $targetKB = 100, int $maxDimension = 1200): bool
    {
        if (!file_exists($sourcePath)) {
            return false;
        }

        $dest = $destinationPath ?: $sourcePath;
        $targetSizeBytes = $targetKB * 1024;

        // If file is already within target size, just copy if destination differs
        if (filesize($sourcePath) <= $targetSizeBytes) {
            if ($sourcePath !== $dest) {
                @copy($sourcePath, $dest);
            }
            return true;
        }

        // Check GD extension support
        if (!function_exists('imagecreatefromstring')) {
            if ($sourcePath !== $dest) {
                @copy($sourcePath, $dest);
            }
            return false;
        }

        try {
            $imageData = @file_get_contents($sourcePath);
            if (!$imageData) {
                return false;
            }

            $srcImg = @imagecreatefromstring($imageData);
            if (!$srcImg) {
                if ($sourcePath !== $dest) {
                    @copy($sourcePath, $dest);
                }
                return false;
            }

            $width = imagesx($srcImg);
            $height = imagesy($srcImg);

            // Step 1: Calculate scaling dimensions if larger than maxDimension
            $newWidth = $width;
            $newHeight = $height;

            if ($width > $maxDimension || $height > $maxDimension) {
                if ($width > $height) {
                    $newHeight = (int) round(($height * $maxDimension) / $width);
                    $newWidth = $maxDimension;
                } else {
                    $newWidth = (int) round(($width * $maxDimension) / $height);
                    $newHeight = $maxDimension;
                }
            }

            // Create scaled canvas
            $dstImg = imagecreatetruecolor($newWidth, $newHeight);

            // Preserve transparency or fill with white for JPEG
            imagealphablending($dstImg, false);
            imagesavealpha($dstImg, true);
            $transparent = imagecolorallocatealpha($dstImg, 255, 255, 255, 127);
            imagefilledrectangle($dstImg, 0, 0, $newWidth, $newHeight, $transparent);
            imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            // Step 2: Iterative quality search for target size <= 100KB
            $qualities = [85, 75, 65, 50, 35, 25, 15];
            $saved = false;

            // Try WebP if supported, otherwise fallback to JPEG
            $supportsWebP = function_exists('imagewebp');

            foreach ($qualities as $q) {
                if ($supportsWebP && (str_ends_with(strtolower($dest), '.webp') || !str_ends_with(strtolower($dest), '.png'))) {
                    @imagewebp($dstImg, $dest, $q);
                } else {
                    // Create solid white background for JPEG
                    $jpgCanvas = imagecreatetruecolor($newWidth, $newHeight);
                    $white = imagecolorallocate($jpgCanvas, 255, 255, 255);
                    imagefilledrectangle($jpgCanvas, 0, 0, $newWidth, $newHeight, $white);
                    imagecopy($jpgCanvas, $dstImg, 0, 0, 0, 0, $newWidth, $newHeight);
                    @imagejpeg($jpgCanvas, $dest, $q);
                    imagedestroy($jpgCanvas);
                }

                clearstatcache(true, $dest);
                if (file_exists($dest) && filesize($dest) <= $targetSizeBytes) {
                    $saved = true;
                    break;
                }
            }

            // If quality adjustment alone was not enough, downscale dimensions step-by-step
            if (!$saved || (file_exists($dest) && filesize($dest) > $targetSizeBytes)) {
                $scaleFactor = 0.8;
                $currentW = $newWidth;
                $currentH = $newHeight;

                while ($currentW > 300 && $currentH > 300) {
                    $currentW = (int) round($currentW * $scaleFactor);
                    $currentH = (int) round($currentH * $scaleFactor);

                    $scaledCanvas = imagecreatetruecolor($currentW, $currentH);
                    $white = imagecolorallocate($scaledCanvas, 255, 255, 255);
                    imagefilledrectangle($scaledCanvas, 0, 0, $currentW, $currentH, $white);
                    imagecopyresampled($scaledCanvas, $dstImg, 0, 0, 0, 0, $currentW, $currentH, $newWidth, $newHeight);

                    @imagejpeg($scaledCanvas, $dest, 40);
                    imagedestroy($scaledCanvas);

                    clearstatcache(true, $dest);
                    if (file_exists($dest) && filesize($dest) <= $targetSizeBytes) {
                        break;
                    }
                }
            }

            imagedestroy($srcImg);
            imagedestroy($dstImg);

            return true;
        } catch (\Throwable $e) {
            if ($sourcePath !== $dest && !file_exists($dest)) {
                @copy($sourcePath, $dest);
            }
            return false;
        }
    }
}
