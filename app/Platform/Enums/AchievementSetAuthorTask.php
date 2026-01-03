<?php

declare(strict_types=1);

namespace App\Platform\Enums;

enum AchievementSetAuthorTask: string
{
    /** TODO rename "artwork" to something like "badge" */
    case Artwork = "artwork";
    case Banner = "banner";

    public function label(): string
    {
        return match ($this) {
            self::Artwork => 'Game Badge Creation',
            self::Banner => 'Banner Artwork',
        };
    }
}
