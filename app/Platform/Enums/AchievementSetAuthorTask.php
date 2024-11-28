<?php

declare(strict_types=1);

namespace App\Platform\Enums;

enum AchievementSetAuthorTask: string
{
    case Artwork = "artwork";

    public function label(): string
    {
        return match ($this) {
            self::Artwork => 'Game Badge Creation',
        };
    }
}
