<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\MediaLibrary\Actions\ExtractBannerEdgeColorsAction;
use Illuminate\Console\Command;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;

class ProcessFallbackBanner extends Command
{
    protected $signature = 'ra:platform:process-fallback-banner';
    protected $description = 'Process the fallback banner image through the same conversions used for user-uploaded banners';

    public function handle(): int
    {
        $sourcePath = public_path('assets/images/banner/fallback.png');

        if (!file_exists($sourcePath)) {
            $this->error("Source file not found: {$sourcePath}");

            return Command::FAILURE;
        }

        $outputDir = public_path('assets/images/banner');
        $bannerSizes = ['mobile-sm', 'mobile-md', 'desktop-md', 'desktop-lg', 'desktop-xl'];
        $formats = ['webp', 'avif'];

        foreach ($bannerSizes as $size) {
            $width = config("media.game.banner.{$size}.width");
            $height = config("media.game.banner.{$size}.height");

            foreach ($formats as $format) {
                Image::load($sourcePath)
                    ->format($format)
                    ->fit(Fit::Crop, $width, $height)
                    ->optimize()
                    ->save("{$outputDir}/fallback-{$size}.{$format}");

                $this->info("Generated: fallback-{$size}.{$format} ({$width}x{$height})");
            }
        }

        // Tiny blurred placeholders load instantly for better LCP scores.
        Image::load($sourcePath)
            ->format('webp')
            ->quality(10)
            ->fit(Fit::Crop, 32, 18)
            ->save("{$outputDir}/fallback-mobile-placeholder.webp");
        $this->info('Generated: fallback-mobile-placeholder.webp (32x18)');

        // Desktop uses a wider aspect ratio than mobile.
        Image::load($sourcePath)
            ->format('webp')
            ->quality(10)
            ->fit(Fit::Crop, 32, 9)
            ->save("{$outputDir}/fallback-desktop-placeholder.webp");
        $this->info('Generated: fallback-desktop-placeholder.webp (32x9)');

        // Edge colors are used to extend the banner with a matching gradient on ultrawide displays.
        $edgeColors = (new ExtractBannerEdgeColorsAction())->execute($sourcePath);

        $this->newLine();
        $this->info('Edge colors extracted:');
        $this->info("  Left:  {$edgeColors['left_edge_color']}");
        $this->info("  Right: {$edgeColors['right_edge_color']}");
        $this->warn('Update the hardcoded edge colors in PageBannerData::fallback() to match.');

        $this->newLine();
        $this->info('All fallback banner conversions generated successfully.');

        return Command::SUCCESS;
    }
}
