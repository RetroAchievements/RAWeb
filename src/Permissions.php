<?php

namespace RA;

abstract class Permissions
{
    public const Spam = -2;

    public const Banned = -1;

    public const Unregistered = 0;

    public const Registered = 1;

    public const JuniorDeveloper = 2;

    public const Developer = 3;

    public const Admin = 4;

    public const AllPermissions = [
        self::Spam,
        self::Banned,
        self::Unregistered,
        self::Registered,
        self::JuniorDeveloper,
        self::Developer,
        self::Admin,
    ];

    public const ValidUserPermissions = [
        self::Unregistered,
        self::Registered,
        self::JuniorDeveloper,
        self::Developer,
        self::Admin,
    ];

    public static function toString(int $permissions): string
    {
        return match ($permissions) {
            Permissions::Spam => 'Spam',
            Permissions::Banned => 'Banned',
            Permissions::Unregistered => 'Unregistered',
            Permissions::Registered => 'Registered',
            Permissions::JuniorDeveloper => 'Junior Developer',
            Permissions::Developer => 'Developer',
            Permissions::Admin => 'Admin',
            default => 'Invalid permission',
        };
    }
}
