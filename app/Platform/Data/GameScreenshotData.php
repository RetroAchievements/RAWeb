<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Models\GameScreenshot;
use App\Platform\Enums\ScreenshotType;
use Spatie\LaravelData\Data;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('GameScreenshot')]
class GameScreenshotData extends Data
{
    public function __construct(
        public int $id,
        public ScreenshotType $type,
        public int $width,
        public int $height,

        public string $originalUrl,
        public string $smWebpUrl,
        public string $mdWebpUrl,
        public string $lgWebpUrl,
    ) {
    }

    public static function fromGameScreenshot(GameScreenshot $screenshot): self
    {
        $media = $screenshot->media;
        $fallback = $media?->getUrl() ?? '';

        return new self(
            id: $screenshot->id,
            type: $screenshot->type,
            width: $screenshot->width,
            height: $screenshot->height,
            originalUrl: $fallback,
            smWebpUrl: self::conversionUrl($media, 'sm-webp', $fallback),
            mdWebpUrl: self::conversionUrl($media, 'md-webp', $fallback),
            lgWebpUrl: self::conversionUrl($media, 'lg-webp', $fallback),
        );
    }

    private static function conversionUrl(?Media $media, string $conversion, string $fallback): string
    {
        // Use the converted format when available, otherwise fall back to the original.
        return $media?->hasGeneratedConversion($conversion) ? $media->getUrl($conversion) : $fallback;
    }
}
