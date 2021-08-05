<?php

namespace RA;

abstract class ArticleType
{
    public const Game = 1;

    public const Achievement = 2;

    public const User = 3;

    public const News = 4;

    public const Activity = 5;

    public const Leaderboard = 6;

    public const AchievementTicket = 7;

    private const VALUES = [
        self::Game,
        self::Achievement,
        self::User,
        self::News,
        self::Activity,
        self::Leaderboard,
        self::AchievementTicket,
    ];

    public static function values(): array
    {
        return self::VALUES;
    }

    public static function isValid($value): bool
    {
        return in_array($value, self::values());
    }
}
