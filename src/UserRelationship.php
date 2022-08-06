<?php

namespace RA;

abstract class UserRelationship
{
    public const Blocked = -1;

    public const NotFollowing = 0;

    public const Following = 1;

    public static function toString(int $type): string
    {
        return match ($type) {
            UserRelationship::Blocked => "Blocked",
            UserRelationship::NotFollowing => "Not following",
            UserRelationship::Following => "Following",
            default => "Invalid friendship type",
        };
    }
}
