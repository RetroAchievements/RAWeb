<?php

namespace RA;

abstract class FriendshipType
{
    public const Blocked = -1;

    public const NotFriend = 0;

    public const Friend = 1;

    public const Pending = 2; // user requested other user as a friend (not in database)

    public const Requested = 3; // other user has requested user as a friend (not in database)

    public const Impossible = 4; // other user has blocked user (not in database)

    public static function toString(int $type): string
    {
        return match ($type) {
            FriendshipType::Blocked => "Blocked",
            FriendshipType::NotFriend => "Not a friend",
            FriendshipType::Friend => "Friend",
            FriendshipType::Pending => "Pending",
            FriendshipType::Requested => "Requested",
            FriendshipType::Impossible => "Impossible",
            default => "Invalid friendship type",
        };
    }
}
