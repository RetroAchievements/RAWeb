<?php

declare(strict_types=1);

namespace App\Support\MediaLibrary\Actions;

use Spatie\Image\Enums\ColorFormat;
use Spatie\Image\Image;

/**
 * Extracts dominant colors from the left and right edges of a banner image.
 * This isn't useful for mobile, but is especially useful for desktop, where
 * we'll have to constrain image width at some point for ultrawide displays.
 *
 * These dominant edge colors can be used to create color-matched gradients on the
 * banner image sides. This gradient can fill the background behind the banner image,
 * creating a seamless  blend when the banner doesn't fill the full container width,
 * which will inevitably happen if the user is using an ultrawide display.
 *
 * Sampling strategy:
 * - Left edge: 10% from left, 50% from top
 * - Right edge: 90% from left, 50% from top
 *
 * Returns custom properties array with:
 * - left_edge_color: Hex color (eg: "#675048") sampled from the left edge.
 * - right_edge_color: Hex color (eg: "#4a312d") sampled from the right edge.
 */
class ExtractBannerEdgeColorsAction
{
    public function execute(string $imagePath): array
    {
        $image = Image::load($imagePath);

        $width = $image->getWidth();
        $height = $image->getHeight();

        // Sample the left edge (10% from left, middle vertically).
        $leftX = (int) ($width * 0.10);
        $leftY = (int) ($height * 0.50);
        $leftColor = $image->pickColor($leftX, $leftY, ColorFormat::Hex);

        // Sample the right edge (90% from left, middle vertically).
        $rightX = (int) ($width * 0.90);
        $rightY = (int) ($height * 0.50);
        $rightColor = $image->pickColor($rightX, $rightY, ColorFormat::Hex);

        return [
            'left_edge_color' => $leftColor,
            'right_edge_color' => $rightColor,
        ];
    }
}
