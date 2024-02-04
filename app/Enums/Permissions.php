<?php

declare(strict_types=1);

namespace App\Enums;

abstract class Permissions
{
    public const Spam = -2;
    public const Banned = -1;
    public const Unregistered = 0;
    public const Registered = 1;
    public const JuniorDeveloper = 2;
    public const Developer = 3;
    public const Moderator = 4;
    public const Admin = 5;
    public const Root = 6;

    public static function cases(): array
    {
        return [
            self::Spam,
            self::Banned,
            self::Unregistered,
            self::Registered,
            self::JuniorDeveloper,
            self::Developer,
            self::Moderator,
        ];
    }

    public static function assignable(): array
    {
        return [
            self::Unregistered,
            self::Registered,
            self::JuniorDeveloper,
            self::Developer,
            self::Moderator,
        ];
    }

    public static function toString(int $permissions): string
    {
        return match ($permissions) {
            Permissions::Spam => 'Spam',
            Permissions::Banned => 'Banned',
            Permissions::Unregistered => 'Unregistered',
            Permissions::Registered => 'Registered',
            Permissions::JuniorDeveloper => 'Junior Developer',
            Permissions::Developer => 'Developer',
            Permissions::Moderator => 'Moderator',
            default => 'Invalid permission',
        };
    }
}
