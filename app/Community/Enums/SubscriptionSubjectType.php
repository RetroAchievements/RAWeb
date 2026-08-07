<?php

declare(strict_types=1);

namespace App\Community\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum SubscriptionSubjectType: string
{
    case ForumTopic = "ForumTopic";
    case UserWall = "UserWall";
    case GameWall = "GameWall";
    case Achievement = "Achievement";
    case Leaderboard = "Leaderboard";
    case GameTickets = "GameTickets";
    case GameAchievements = "GameAchievements";
    case AchievementTicket = "AchievementTicket";
    case GameScreenshotDecision = "GameScreenshotDecision";
    case AchievementSetRelease = "AchievementSetRelease";

    /**
     * The order the daily digest gives to subject types. The first type present in a
     * digest names the subject line, and the email body lists that type's items first.
     *
     * @return SubscriptionSubjectType[]
     */
    public static function digestHeadlinePriority(): array
    {
        return [
            self::AchievementSetRelease,
            self::GameScreenshotDecision,
            self::ForumTopic,
            self::AchievementTicket,
        ];
    }

    public static function fromCommentableType(CommentableType $commentableType): ?SubscriptionSubjectType
    {
        return match ($commentableType) {
            CommentableType::Game => SubscriptionSubjectType::GameWall,
            CommentableType::Achievement => SubscriptionSubjectType::Achievement,
            CommentableType::Leaderboard => SubscriptionSubjectType::Leaderboard,
            CommentableType::User => SubscriptionSubjectType::UserWall,
            CommentableType::AchievementTicket => SubscriptionSubjectType::AchievementTicket,
            default => null,
        };
    }
}
