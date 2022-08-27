<?php

namespace RA;

// TODO split requests
abstract class UserAction
{
    public const UpdatePermissions = 0;

    public const UpdateForumPostPermissions = 1;

    public const PatreonBadge = 2;

    public const TrackedStatus = 3;

    public static function cases(): array
    {
        return [
            self::UpdatePermissions,
            self::UpdateForumPostPermissions,
            self::PatreonBadge,
            self::TrackedStatus,
        ];
    }
}
