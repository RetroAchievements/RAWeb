<?php

declare(strict_types=1);

namespace App\Platform\Enums;

enum AchievementAuthorTask: string
{
    case Artwork = "artwork";
    case Logic = "logic";
    case Testing = "testing";
    case Writing = "writing";

    public function label(): string
    {
        return match ($this) {
            self::Artwork => 'Artwork',
            self::Logic => 'Logic',
            self::Testing => 'Testing',
            self::Writing => 'Writing',
        };
    }
}
