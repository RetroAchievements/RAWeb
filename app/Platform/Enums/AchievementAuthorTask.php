<?php

declare(strict_types=1);

namespace App\Platform\Enums;

enum AchievementAuthorTask: string
{
    case ARTWORK = "artwork";
    case LOGIC = "logic";
    case TESTING = "testing";
    case WRITING = "writing";

    public function label(): string
    {
        return match ($this) {
            self::ARTWORK => 'Artwork',
            self::LOGIC => 'Logic',
            self::TESTING => 'Testing',
            self::WRITING => 'Writing',
        };
    }
}
