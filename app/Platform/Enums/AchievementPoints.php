<?php

declare(strict_types=1);

namespace App\Platform\Enums;

abstract class AchievementPoints
{
    public static function cases(): array
    {
        return [0, 1, 2, 3, 4, 5, 10, 25, 50, 100];
    }

    public static function isValid(int $points): bool
    {
        return in_array($points, self::cases());
    }
}
