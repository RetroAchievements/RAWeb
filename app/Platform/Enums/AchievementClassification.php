<?php

declare(strict_types=1);

namespace App\Platform\Enums;

abstract class AchievementClassification
{
    public const Progression = 1;
    public const WinCondition = 2;

    public static function cases(): array
    {
        return [
            self::Progression,
            self::WinCondition,
        ];
    }

    public static function isValid(int $classification): bool
    {
        return in_array($classification, self::cases());
    }
}
