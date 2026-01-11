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
    case EventWall = "EventWall";
    case Achievement = "Achievement";
    case Leaderboard = "Leaderboard";
    case GameTickets = "GameTickets";
    case GameAchievements = "GameAchievements";
    case AchievementTicket = "AchievementTicket";

    public static function fromCommentableType(CommentableType $commentableType): ?SubscriptionSubjectType
    {
        return match ($commentableType) {
            CommentableType::Game => SubscriptionSubjectType::GameWall,
            CommentableType::Event => SubscriptionSubjectType::EventWall,
            CommentableType::Achievement => SubscriptionSubjectType::Achievement,
            CommentableType::Leaderboard => SubscriptionSubjectType::Leaderboard,
            CommentableType::User => SubscriptionSubjectType::UserWall,
            CommentableType::AchievementTicket => SubscriptionSubjectType::AchievementTicket,
            default => null,
        };
    }
}
