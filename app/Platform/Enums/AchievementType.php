<?php

declare(strict_types=1);

namespace App\Platform\Enums;

abstract class AchievementType
{
    public const OfficialCore = 3;

    public const Unofficial = 5;

    public static function cases(): array
    {
        return [
            self::OfficialCore,
            self::Unofficial,
        ];
    }

    public static function isValid(int $type): bool
    {
        return in_array($type, self::cases());
    }
}
