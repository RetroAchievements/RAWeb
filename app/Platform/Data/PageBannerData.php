<?php

declare(strict_types=1);

namespace App\Platform\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('PageBanner')]
class PageBannerData extends Data
{
    public function __construct(
        public ?string $mobileSmWebp,
        public ?string $mobileSmAvif,
        public ?string $mobileMdWebp,
        public ?string $mobileMdAvif,
        public ?string $desktopMdWebp,
        public ?string $desktopMdAvif,
        public ?string $desktopLgWebp,
        public ?string $desktopLgAvif,
        public ?string $desktopXlWebp,
        public ?string $desktopXlAvif,
        public ?string $mobilePlaceholder,
        public ?string $desktopPlaceholder,
        public ?string $leftEdgeColor,
        public ?string $rightEdgeColor,
        public bool $isFallback = false,
    ) {
    }

    public static function fallback(): self
    {
        $base = 'assets/images/banner';

        return new self(
            mobileSmWebp: asset("{$base}/fallback-mobile-sm.webp"),
            mobileSmAvif: null,
            mobileMdWebp: asset("{$base}/fallback-mobile-md.webp"),
            mobileMdAvif: null,
            desktopMdWebp: asset("{$base}/fallback-desktop-md.webp"),
            desktopMdAvif: null,
            desktopLgWebp: asset("{$base}/fallback-desktop-lg.webp"),
            desktopLgAvif: null,
            desktopXlWebp: asset("{$base}/fallback-desktop-xl.webp"),
            desktopXlAvif: null,
            mobilePlaceholder: asset("{$base}/fallback-mobile-placeholder.webp"),
            desktopPlaceholder: asset("{$base}/fallback-desktop-placeholder.webp"),
            leftEdgeColor: '#151936',
            rightEdgeColor: '#261c12',
            isFallback: true,
        );
    }
}
