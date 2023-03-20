<?php

declare(strict_types=1);

namespace LegacyApp\Community\Enums;

abstract class ActivityType
{
    public const EarnedAchievement = 1;

    public const Login = 2;

    public const StartedPlaying = 3;

    public const UploadAchievement = 4;

    public const EditAchievement = 5;

    public const CompleteGame = 6;

    public const NewLeaderboardEntry = 7;

    public const ImprovedLeaderboardEntry = 8;

    public const OpenedTicket = 9;

    public const ClosedTicket = 10;

    public static function cases(): array
    {
        return [
            self::EarnedAchievement,
            self::Login,
            self::StartedPlaying,
            self::UploadAchievement,
            self::EditAchievement,
            self::CompleteGame,
            self::NewLeaderboardEntry,
            self::ImprovedLeaderboardEntry,
            self::OpenedTicket,
            self::ClosedTicket,
        ];
    }

    public static function isValid(int $value): bool
    {
        return in_array($value, self::cases());
    }
}
