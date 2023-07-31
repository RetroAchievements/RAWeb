<?php

declare(strict_types=1);

namespace App\Platform\Enums;

abstract class AchievementFlag
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

    public static function isValid(int $flag): bool
    {
        return in_array($flag, self::cases());
    }
}
