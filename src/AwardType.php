<?php

namespace RA;

abstract class AwardType
{
    public const MASTERY = 1;

    public const ACHIEVEMENT_UNLOCKS_YIELD = 2;

    public const ACHIEVEMENT_POINTS_YIELD = 3;

    public const REFERRALS = 4;

    public const FACEBOOK_CONNECT = 5;

    public const PATREON_SUPPORTER = 6;

    public const ACTIVE = [
        self::MASTERY,
        self::ACHIEVEMENT_UNLOCKS_YIELD,
        self::ACHIEVEMENT_POINTS_YIELD,
        self::PATREON_SUPPORTER,
    ];

    public static function isActive(int $value): bool
    {
        return in_array($value, self::ACTIVE);
    }
}
