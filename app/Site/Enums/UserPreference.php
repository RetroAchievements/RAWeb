<?php

declare(strict_types=1);

namespace App\Site\Enums;

abstract class UserPreference
{
    public const EmailOn_ActivityComment = 0;

    public const EmailOn_AchievementComment = 1;

    public const EmailOn_UserWallComment = 2;

    public const EmailOn_ForumReply = 3;

    public const EmailOn_Followed = 4;

    public const EmailOn_PrivateMessage = 5;

    public const EmailOn_Newsletter = 6;

    public const Site_SuppressMatureContentWarning = 7;

    public const SiteMsgOn_ActivityComment = 8;

    public const SiteMsgOn_AchievementComment = 9;

    public const SiteMsgOn_UserWallComment = 10;

    public const SiteMsgOn_ForumReply = 11;

    public const SiteMsgOn_Followed = 12;

    public const SiteMsgOn_PrivateMessage = 13;

    public const SiteMsgOn_Newsletter = 14;

    public const Forum_ShowAbsoluteDates = 15;
}
