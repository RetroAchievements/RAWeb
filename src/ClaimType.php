<?php

namespace RA;

abstract class ClaimType
{
    public const Primary = 0;
    public const Collaboration = 1;

    public static function toString(int $type): string
    {
        return match ($type) {
            self::Primary => "Primary",
            self::Collaboration => "Collaboration",
            default => "Invalid state",
        };
    }
}
