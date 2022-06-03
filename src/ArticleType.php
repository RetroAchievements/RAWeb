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

    public const Forum = 8;

    public const UserModeration = 9;

    public const GameHash = 10;

    public const GameModification = 11;

    private const VALID = [
        self::Game,
        self::Achievement,
        self::User,
        self::News,
        self::Activity,
        self::Leaderboard,
        self::AchievementTicket,
        self::Forum,
        self::UserModeration,
        self::GameHash,
        self::GameModification,
    ];

    public static function isValid($value): bool
    {
        return in_array($value, self::VALID);
    }
}
