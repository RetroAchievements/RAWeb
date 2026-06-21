<?php

declare(strict_types=1);

namespace App\Platform\Enums;

enum ScreenshotType: string
{
    case Title = 'title';
    case Ingame = 'ingame';
    case Completion = 'completion';

    public function label(): string
    {
        return match ($this) {
            self::Title => 'Title',
            self::Ingame => 'In-game',
            self::Completion => 'Completion',
        };
    }

    public function approvedCap(): int
    {
        return match ($this) {
            self::Title, self::Completion => 1,
            self::Ingame => 10,
        };
    }

    public function sortOrder(): int
    {
        return match ($this) {
            self::Title => 0,
            self::Ingame => 1,
            self::Completion => 2,
        };
    }

    public function legacyAssetPathField(): ?string
    {
        return match ($this) {
            self::Title => 'image_title_asset_path',
            self::Ingame => 'image_ingame_asset_path',
            self::Completion => null,
        };
    }
}
