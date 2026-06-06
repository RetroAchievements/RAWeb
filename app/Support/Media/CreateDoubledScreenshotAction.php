<?php

declare(strict_types=1);

namespace App\Support\Media;

class CreateDoubledScreenshotAction
{
    /**
     * Doubles the width of an image using nearest-neighbor scaling,
     * preserving pixel art. Returns the path to a temp PNG file.
     *
     * Uses imagecopyresized() instead of imagecopyresampled()
     * because resampled applies bilinear interpolation that
     * blurs pixel art.
     */
    public function execute(string $imageContents): string
    {
        $sourceImage = imagecreatefromstring($imageContents);

        $sourceWidth = imagesx($sourceImage);
        $sourceHeight = imagesy($sourceImage);

        $targetWidth = $sourceWidth * 2;
        $targetHeight = $sourceHeight;

        $doubled = imagecreatetruecolor($targetWidth, $targetHeight);

        imagecopyresized(
            $doubled,
            $sourceImage,
            0, 0, 0, 0,
            $targetWidth, $targetHeight,
            $sourceWidth, $sourceHeight,
        );

        $tempPath = tempnam(sys_get_temp_dir(), 'doubled_screenshot_') . '.png';

        imagepng($doubled, $tempPath);

        return $tempPath;
    }
}
