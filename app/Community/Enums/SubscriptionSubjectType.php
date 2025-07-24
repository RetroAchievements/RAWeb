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
    case GameTickets = "GameTickets";
    case GameAchievements = "GameAchievements";
    case AchievementTicket = "AchievementTicket";

    public static function fromArticleType(int $articleType): ?SubscriptionSubjectType
    {
        return match ($articleType) {
            ArticleType::Game => SubscriptionSubjectType::GameWall,
            ArticleType::Achievement => SubscriptionSubjectType::Achievement,
            ArticleType::User => SubscriptionSubjectType::UserWall,
            ArticleType::AchievementTicket => SubscriptionSubjectType::AchievementTicket,
            default => null,
        };
    }
}
