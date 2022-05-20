<?php

declare(strict_types=1);

namespace App\Community\Enums;

abstract class ClaimSetType
{
    public const NewSet = 0;
    public const Revision = 1;

    public static function cases(): array
    {
        return [
            self::NewSet,
            self::Revision,
        ];
    }

    public static function toString(int $type): string
    {
        return match ($type) {
            self::NewSet => "New",
            self::Revision => "Revision",
            default => "Invalid set type",
        };
    }
}
