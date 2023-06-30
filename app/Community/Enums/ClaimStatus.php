<?php

declare(strict_types=1);

namespace App\Community\Enums;

abstract class ClaimStatus
{
    public const Active = 0;
    public const Complete = 1;
    public const Dropped = 2;

    public static function cases(): array
    {
        return [
            self::Active,
            self::Complete,
            self::Dropped,
        ];
    }

    public static function toString(int $type): string
    {
        return match ($type) {
            self::Active => "Active",
            self::Complete => "Complete",
            self::Dropped => "Dropped",
            default => "Invalid status",
        };
    }
}
