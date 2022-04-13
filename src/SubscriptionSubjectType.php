<?php

namespace RA;

abstract class SubscriptionSubjectType
{
    public const ForumTopic = "ForumTopic";

    public const UserWall = "UserWall";

    public const GameWall = "GameWall";

    public const Achievement = "Achievement";

    public const GameTickets = "GameTickets";

    public const GameAchievements = "GameAchievements";

    public static function fromArticleType($articleType)
    {
        return match ($articleType) {
            ArticleType::Game => SubscriptionSubjectType::GameWall,
            ArticleType::Achievement => SubscriptionSubjectType::Achievement,
            ArticleType::User => SubscriptionSubjectType::UserWall,
            default => null,
        };
    }
}
