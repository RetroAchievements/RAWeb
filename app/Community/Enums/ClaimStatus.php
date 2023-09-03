<?php

declare(strict_types=1);

namespace App\Community\Enums;

abstract class ClaimStatus
{
    public const Active = 0;
    public const Complete = 1;
    public const Dropped = 2;
    public const InReview = 3;

    public static function cases(): array
    {
        return [
            self::Active,
            self::Complete,
            self::Dropped,
            self::InReview,
        ];
    }

    public static function toString(int $type): string
    {
        return match ($type) {
            self::Active => "Active",
            self::Complete => "Complete",
            self::Dropped => "Dropped",
            self::InReview => "In Review",
            default => "Invalid status",
        };
    }

    public static function isActive(int $type): bool
    {
        return match ($type) {
            self::Active => true,
            self::InReview => true,
            default => false,
        };
    }
}
