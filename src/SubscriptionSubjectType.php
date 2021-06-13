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
        switch ($articleType) {
            case ArticleType::Game:
                return SubscriptionSubjectType::GameWall;
            case ArticleType::Achievement:
                return SubscriptionSubjectType::Achievement;
            case ArticleType::User:
                return SubscriptionSubjectType::UserWall;
        }

        return null;
    }
}
