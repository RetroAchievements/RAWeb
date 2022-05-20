<?php

namespace RA;

abstract class AchievementType
{
    public const OFFICIAL_CORE = 3;
    public const UNOFFICIAL = 5;

    private const VALID = [
        self::OFFICIAL_CORE,
        self::UNOFFICIAL,
    ];

    public static function isValid(int $type): bool
    {
        return in_array($type, self::VALID);
    }
}
