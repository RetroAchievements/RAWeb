<?php

namespace RA;

abstract class SubscriptionSubjectType
{
  const ForumTopic         = "ForumTopic";
  const UserWall           = "UserWall";
  const GameWall           = "GameWall";
  const Achievement        = "Achievement";

  const GameTickets        = "GameTickets";
  const GameAchievements   = "GameAchievements";

  public static function fromArticleType($articleType)
  {
    switch ($articleType)
    {
      case 1: return SubscriptionSubjectType::GameWall;
      case 2: return SubscriptionSubjectType::Achievement;
      case 3: return SubscriptionSubjectType::UserWall;
    }

    return null;
  }
}

?>