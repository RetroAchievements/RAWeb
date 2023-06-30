<?php

declare(strict_types=1);

namespace App\Community\Enums;

// TODO split requests
abstract class UserAction
{
    public const UpdatePermissions = 0;

    public const UpdateForumPostPermissions = 1;

    public const PatreonBadge = 2;

    public const TrackedStatus = 3;

    public const LegendBadge = 4;

    public static function cases(): array
    {
        return [
            self::UpdatePermissions,
            self::UpdateForumPostPermissions,
            self::PatreonBadge,
            self::TrackedStatus,
            self::LegendBadge,
        ];
    }
}
