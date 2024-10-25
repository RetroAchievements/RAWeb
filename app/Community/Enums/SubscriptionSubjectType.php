<?php

declare(strict_types=1);

namespace App\Community\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
abstract class SubscriptionSubjectType
{
    public const ForumTopic = "ForumTopic";

    public const UserWall = "UserWall";

    public const GameWall = "GameWall";

    public const Achievement = "Achievement";

    public const GameTickets = "GameTickets";

    public const GameAchievements = "GameAchievements";

    public static function fromArticleType(int $articleType): ?string
    {
        return match ($articleType) {
            ArticleType::Game => SubscriptionSubjectType::GameWall,
            ArticleType::Achievement => SubscriptionSubjectType::Achievement,
            ArticleType::User => SubscriptionSubjectType::UserWall,
            default => null,
        };
    }

    public static function cases(): array
    {
        return [
            self::ForumTopic,
            self::UserWall,
            self::GameWall,
            self::Achievement,
            self::GameTickets,
            self::GameAchievements,
        ];
    }

    public static function isValid(string $subjectType): bool
    {
        return in_array($subjectType, self::cases());
    }
}
