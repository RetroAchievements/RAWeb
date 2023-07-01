<?php

declare(strict_types=1);

namespace App\Community\Enums;

abstract class ClaimType
{
    public const Primary = 0;
    public const Collaboration = 1;

    public static function cases(): array
    {
        return [
            self::Primary,
            self::Collaboration,
        ];
    }

    public static function toString(int $type): string
    {
        return match ($type) {
            self::Primary => "Primary",
            self::Collaboration => "Collaboration",
            default => "Invalid state",
        };
    }
}
