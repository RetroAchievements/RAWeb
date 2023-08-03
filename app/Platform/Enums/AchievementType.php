<?php

declare(strict_types=1);

namespace App\Platform\Enums;

abstract class AchievementType
{
    public const Progression = 'Progression';

    public const WinCondition = 'Win Condition';

    public static function cases(): array
    {
        return [
            self::Progression,
            self::WinCondition,
        ];
    }

    public static function isValid(string $type): bool
    {
        return in_array($type, self::cases());
    }
}
