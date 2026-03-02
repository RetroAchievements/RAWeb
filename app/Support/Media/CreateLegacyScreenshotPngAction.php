<?php

declare(strict_types=1);

namespace App\Support\Media;

class CreateLegacyScreenshotPngAction
{
    private const MAX_WIDTH = 320;
    private const MAX_HEIGHT = 240;

    /**
     * Resizes a source image to fit within 320x240 (preserving aspect ratio),
     * saves it as a legacy PNG under /Images/NNNNNN.png, and uploads to S3.
     *
     * This keeps the legacy image_*_asset_path columns populated with paths
     * that media_asset() can resolve.
     */
    public function execute(string $imageContents): ?string
    {
        $sourceImage = imagecreatefromstring($imageContents);
        if (!$sourceImage) {
            return null;
        }

        $sourceWidth = imagesx($sourceImage);
        $sourceHeight = imagesy($sourceImage);

        [$targetWidth, $targetHeight] = $this->calculateTargetDimensions($sourceWidth, $sourceHeight);

        $resized = imagecreatetruecolor($targetWidth, $targetHeight);

        imagecopyresized($resized, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

        $imagePath = '/Images/' . FilenameIterator::getImageIterator() . '.png';
        $localPath = storage_path('app/media' . $imagePath);

        // Ensure the directory exists.
        $dir = dirname($localPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        imagepng($resized, $localPath);
        FilenameIterator::incrementImageIterator();

        imagedestroy($sourceImage);
        imagedestroy($resized);

        UploadToS3($localPath, $imagePath);

        return $imagePath;
    }

    /**
     * @return array{int, int}
     */
    private function calculateTargetDimensions(int $sourceWidth, int $sourceHeight): array
    {
        $targetWidth = $sourceWidth;
        $targetHeight = $sourceHeight;

        if ($targetWidth > self::MAX_WIDTH) {
            $scale = self::MAX_WIDTH / $targetWidth;
            $targetWidth = (int) ($targetWidth * $scale);
            $targetHeight = (int) ($targetHeight * $scale);
        }

        if ($targetHeight > self::MAX_HEIGHT) {
            $scale = self::MAX_HEIGHT / $targetHeight;
            $targetWidth = (int) ($targetWidth * $scale);
            $targetHeight = (int) ($targetHeight * $scale);
        }

        return [$targetWidth, $targetHeight];
    }
}
