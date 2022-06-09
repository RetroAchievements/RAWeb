<?php

namespace RA;

abstract class ClaimStatus
{
    public const Active = 0;
    public const Complete = 1;
    public const Dropped = 2;

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
