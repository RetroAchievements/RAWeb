<?php

declare(strict_types=1);

namespace App\Site\Enums;

abstract class UserPreference
{
    // bit index
    public const EmailOn_ActivityComment = 0;
    public const EmailOn_AchievementComment = 1;
    public const EmailOn_UserWallComment = 2;
    public const EmailOn_ForumReply = 3;
    public const EmailOn_Followed = 4;
    public const EmailOn_PrivateMessage = 5;
    public const EmailOn_Newsletter = 6;

    public const HideMatureContent = 7;

    public const SiteMsgOn_ActivityComment = 8;
    public const SiteMsgOn_AchievementComment = 9;
    public const SiteMsgOn_UserWallComment = 10;
    public const SiteMsgOn_ForumReply = 11;
    public const SiteMsgOn_Followed = 12;
    public const SiteMsgOn_PrivateMessage = 13;
    public const SiteMsgOn_Newsletter = 14;

    public const Forum_ShowAbsoluteDates = 15;

    public static function cases(): array
    {
        return [
            self::EmailOn_ActivityComment,
            self::EmailOn_AchievementComment,
            self::EmailOn_UserWallComment,
            self::EmailOn_ForumReply,
            self::EmailOn_Followed,
            self::EmailOn_PrivateMessage,
            self::EmailOn_Newsletter,
            self::HideMatureContent,
        ];
    }

    public static function valid(int $preference)
    {
        return in_array($preference, self::cases());
    }
}
