<?php

namespace RA;

abstract class AchievementPoints
{
    public const cases = [0, 1, 2, 3, 4, 5, 10, 25, 50, 100];

    public static function isValid(int $points): bool
    {
        return in_array($points, self::cases);
    }
}
