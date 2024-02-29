<?php

declare(strict_types=1);

namespace App\Platform\Enums;

abstract class SystemFlag
{
    public const AllSystems = 0;

    public const ActiveSystems = 1;

    public static function cases(): array
    {
        return [
            self::AllSystems,
            self::ActiveSystems,
        ];
    }

    public static function isValid(int $flag): bool
    {
        return in_array($flag, self::cases());
    }
}
