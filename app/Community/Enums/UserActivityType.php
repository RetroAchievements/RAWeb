<?php

declare(strict_types=1);

namespace App\Community\Enums;

abstract class UserActivityType
{
    public const UnlockedAchievement = 'player-achievement.create';

    public const Login = 'login';

    public const StartedPlaying = 'player-session.create';

    public const UploadAchievement = 'achievement.create';

    public const EditAchievement = 'achievement.update';

    public const CompleteAchievementSet = 'achievement-set.complete';
    public const BeatAchievementSet = 'achievement-set.beat';

    public const NewLeaderboardEntry = 'leaderboard-entry.create';

    public const ImprovedLeaderboardEntry = 'leaderboard-entry.update';

    public const OpenedTicket = 'trigger.ticket.create';

    public const ClosedTicket = 'trigger.ticket.delete';

    public static function cases(): array
    {
        return [
            self::UnlockedAchievement,
            self::Login,
            self::StartedPlaying,
            self::UploadAchievement,
            self::EditAchievement,
            self::CompleteAchievementSet,
            self::BeatAchievementSet,
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
