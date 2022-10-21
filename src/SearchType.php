<?php

namespace RA;

abstract class SearchType
{
    public const All = 0;

    public const Game = 1;

    public const Achievement = 2;

    public const User = 3;

    public const Forum = 4;

    public const Comment = 5;

    public static function cases(): array
    {
        return [
            self::All,
            self::Game,
            self::Achievement,
            self::User,
            self::Forum,
            self::Comment,
        ];
    }

    public static function isValid(int $type): bool
    {
        return in_array($type, self::cases());
    }

    public static function toString(int $type): string
    {
        return match ($type) {
            SearchType::All => "Everything",
            SearchType::Game => "Games",
            SearchType::Achievement => "Achievements",
            SearchType::User => "Users",
            SearchType::Forum => "Forums",
            SearchType::Comment => "Comments",
            default => "Invalid search type",
        };
    }
}
