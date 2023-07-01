<?php

declare(strict_types=1);

namespace App\Site\Enums;

abstract class SearchType
{
    public const All = 0;

    public const Game = 1;

    public const Achievement = 2;

    public const User = 3;

    public const Forum = 4;

    public const GameComment = 5;

    public const AchievementComment = 6;

    public const LeaderboardComment = 7;

    public const UserComment = 8;

    public const TicketComment = 9;

    public const GameHashComment = 10;

    public const SetClaimComment = 11;

    public const UserModerationComment = 12;

    public static function cases(): array
    {
        // NOTE: this order determines the order of the items in the 'search in' dropdown
        //       the actual numerical order determines the order of the results
        return [
            self::All,
            self::Game,
            self::Achievement,
            self::User,
            self::Forum,
            self::GameComment,
            self::AchievementComment,
            self::LeaderboardComment,
            self::UserComment,
            self::TicketComment,
            self::GameHashComment,
            self::SetClaimComment,
            self::UserModerationComment,
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
            SearchType::GameComment => "Game Comments",
            SearchType::AchievementComment => "Achievement Comments",
            SearchType::LeaderboardComment => "Leaderboard Comments",
            SearchType::TicketComment => "Ticket Comments",
            SearchType::UserComment => "User Wall Comments",
            SearchType::UserModerationComment => "User Moderation Comments",
            SearchType::GameHashComment => "Game Hash Comments",
            SearchType::SetClaimComment => "Set Claim Comments",
            default => "Invalid search type",
        };
    }
}
