<?php

namespace RA;

abstract class SubscriptionSubjectType
{
    const ForumTopic = "ForumTopic";

    const UserWall = "UserWall";

    const GameWall = "GameWall";

    const Achievement = "Achievement";

    const GameTickets = "GameTickets";

    const GameAchievements = "GameAchievements";

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
