<?php

declare(strict_types=1);

namespace App\Community\Enums;

abstract class ActivityType
{
    // TODO remove - activity types can be derived from respective tables; user activity log will work differently
    public const UnlockedAchievement = 1;

    public const Login = 2;

    public const StartedPlaying = 3;

    public const UploadAchievement = 4;

    public const EditAchievement = 5;

    public const CompleteGame = 6;

    public const NewLeaderboardEntry = 7;

    public const ImprovedLeaderboardEntry = 8;

    public const OpenedTicket = 9;

    public const ClosedTicket = 10;

    public const BeatGame = 11;

    public static function cases(): array
    {
        return [
            self::UnlockedAchievement,
            self::Login,
            self::StartedPlaying,
            self::UploadAchievement,
            self::EditAchievement,
            self::CompleteGame,
            self::NewLeaderboardEntry,
            self::ImprovedLeaderboardEntry,
            self::OpenedTicket,
            self::ClosedTicket,
            self::BeatGame,
        ];
    }

    public static function isValid(int $value): bool
    {
        return in_array($value, self::cases());
    }
}
