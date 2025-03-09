<?php

namespace App\Service;

class ImageWatermarkService
{
    private $projectDir;
    private $useGD;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
        $this->useGD = extension_loaded('gd') && function_exists('imagecreatefromjpeg');
    }

    public function addWatermark(string $imagePath): void
    {
        if (!$this->useGD) {
            throw new \RuntimeException('GD extension is required for watermarking');
        }

        try {
            // Get image info
            $imageInfo = getimagesize($imagePath);
            if (!$imageInfo) {
                throw new \RuntimeException('Could not read image file');
            }

            // Create image resource based on file type
            $source = match ($imageInfo[2]) {
                IMAGETYPE_JPEG => imagecreatefromjpeg($imagePath),
                IMAGETYPE_PNG => imagecreatefrompng($imagePath),
                IMAGETYPE_GIF => imagecreatefromgif($imagePath),
                IMAGETYPE_WEBP => imagecreatefromwebp($imagePath),
                default => throw new \RuntimeException('Unsupported image type: ' . $imageInfo[2])
            };

            if (!$source) {
                throw new \RuntimeException('Could not create image resource');
            }

            // For PNG and WebP images, preserve transparency
            if ($imageInfo[2] === IMAGETYPE_PNG || $imageInfo[2] === IMAGETYPE_WEBP) {
                imagealphablending($source, true);
                imagesavealpha($source, true);
            }

            // Get dimensions
            $width = imagesx($source);
            $height = imagesy($source);

            // Calculate font size (5% of smallest dimension)
            $fontSize = min($width, $height) * 0.05;

            // Create colors for the text (white with black shadow)
            $white = imagecolorallocatealpha($source, 255, 255, 255, 20); // More opaque white
            $black = imagecolorallocatealpha($source, 0, 0, 0, 40); // Semi-transparent black

            // Get the font path
            $fontPath = $this->projectDir . '/public/fonts/arial.ttf';

            if (!file_exists($fontPath)) {
                throw new \RuntimeException('Font file not found: ' . $fontPath);
            }

            // Text to add
            $text = 'wonderwise';

            // Get the bounding box of the text
            $bbox = imagettfbbox($fontSize, 0, $fontPath, $text);
            $textWidth = $bbox[2] - $bbox[0];
            $textHeight = $bbox[1] - $bbox[7];

            // Calculate position (bottom right corner with padding)
            $padding = 20;
            $x = $width - $textWidth - $padding;
            $y = $height - $padding;

            // Add the shadow text
            imagettftext(
                $source,
                $fontSize,
                0,
                $x + 2,
                $y + 2,
                $black,
                $fontPath,
                $text
            );

            // Add the main text
            imagettftext(
                $source,
                $fontSize,
                0,
                $x,
                $y,
                $white,
                $fontPath,
                $text
            );

            // Save the image based on original type with high quality
            switch ($imageInfo[2]) {
                case IMAGETYPE_JPEG:
                    imagejpeg($source, $imagePath, 95);
                    break;
                case IMAGETYPE_PNG:
                    imagepng($source, $imagePath, 6);
                    break;
                case IMAGETYPE_GIF:
                    imagegif($source, $imagePath);
                    break;
                case IMAGETYPE_WEBP:
                    imagewebp($source, $imagePath, 95);
                    break;
            }

            // Free memory
            imagedestroy($source);
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to add watermark: ' . $e->getMessage());
        }
    }
}
