<?php

namespace RA;

abstract class AchievementType
{
    public const OfficialCore = 3;

    public const Unofficial = 5;

    private const VALID = [
        self::OfficialCore,
        self::Unofficial,
    ];

    public static function isValid(int $type): bool
    {
        return in_array($type, self::VALID);
    }
}
