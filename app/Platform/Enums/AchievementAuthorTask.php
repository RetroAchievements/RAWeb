<?php

declare(strict_types=1);

namespace App\Platform\Enums;

enum AchievementAuthorTask: string
{
    case Artwork = "artwork";
    case Design = "design";
    case Logic = "logic";
    case Testing = "testing";
    case Writing = "writing";

    public function label(): string
    {
        return match ($this) {
            self::Artwork => 'Artwork',
            self::Design => 'Design',
            self::Logic => 'Logic',
            self::Testing => 'Testing',
            self::Writing => 'Writing',
        };
    }
}
