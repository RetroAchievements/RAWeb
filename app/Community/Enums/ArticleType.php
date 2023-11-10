<?php

declare(strict_types=1);

namespace App\Community\Enums;

abstract class ArticleType
{
    public const Game = 1; // TODO commentable_type = game

    public const Achievement = 2; // TODO commentable_type = achievement

    public const User = 3; // TODO commentable_type = user

    public const News = 4; // TODO commentable_type = news

    // public const Activity = 5; // deprecated

    public const Leaderboard = 6; // TODO commentable_type = leaderboard

    public const AchievementTicket = 7; // TODO commentable_type = ticket

    public const Forum = 8; // TODO ??? used for email notifications, not for comments

    public const UserModeration = 9; // TODO migrate to audit log

    public const GameHash = 10; // TODO migrate to audit log

    public const SetClaim = 11; // TODO migrate to audit log

    public const GameModification = 12; // TODO migrate to audit log

    public static function cases(): array
    {
        return [
            self::Game,
            self::Achievement,
            self::User,
            self::News,
            self::Leaderboard,
            self::AchievementTicket,
            self::Forum,
            self::UserModeration,
            self::GameHash,
            self::SetClaim,
            self::GameModification,
        ];
    }

    public static function isValid(int $value): bool
    {
        return in_array($value, self::cases());
    }
}
