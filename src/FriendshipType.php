<?php

namespace RA;

abstract class FriendshipType
{
    public const Blocked = -1;

    public const NotFollowing = 0;

    public const Following = 1;

    public static function toString(int $type): string
    {
        return match ($type) {
            FriendshipType::Blocked => "Blocked",
            FriendshipType::NotFollowing => "Not following",
            FriendshipType::Following => "Following",
            default => "Invalid friendship type",
        };
    }
}
