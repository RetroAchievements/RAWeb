<?php

namespace RA;

abstract class ArticleType
{
    const Game = 1;

    const Achievement = 2;

    const User = 3;

    const News = 4;

    const Activity = 5;

    const Leaderboard = 6;

    const AchievementTicket = 7;

    private const VALUES = [
        self::Game,
        self::Achievement,
        self::User,
        self::Activity,
        self::Leaderboard,
        self::AchievementTicket,
    ];

    public static function values(): array
    {
        return self::VALUES;
    }

    public static function isValue($value): bool
    {
        return in_array($value, self::values());
    }
}
