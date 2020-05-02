<?php

namespace RA;

abstract class ArticleType
{
    public const Game = 1;

    public const Achievement = 2;

    public const User = 3;

    public const Feed = 5;

    public const Leaderboard = 6;

    public const Ticket = 7;

    private const VALUES = [
        self::Game,
        self::Achievement,
        self::User,
        self::Feed,
        self::Leaderboard,
        self::Ticket,
    ];

    public static function values(): array
    {
        return self::VALUES;
    }
}
