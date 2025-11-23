<?php

declare(strict_types=1);

namespace App\Platform\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('GameBanner')]
class GameBannerData extends Data
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
    ) {
    }
}
