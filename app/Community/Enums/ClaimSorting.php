<?php

declare(strict_types=1);

namespace App\Community\Enums;

abstract class ClaimSorting
{
    // Descending sorting
    public const UserDescending = 2;

    public const GameDescending = 3;

    public const ClaimTypeDescending = 4;

    public const SetTypeDescending = 5;

    public const ClaimStatusDescending = 6;

    public const SpecialDescending = 7;

    public const ClaimDateDescending = 8;

    public const FinishedDateDescending = 9;

    // Ascending sorting
    public const UserAscending = 12;

    public const GameAscending = 13;

    public const ClaimTypeAscending = 14;

    public const SetTypeAscending = 15;

    public const ClaimStatusAscending = 16;

    public const SpecialAscending = 17;

    public const ClaimDateAscending = 18;

    public const FinishedDateAscending = 19;

    public static function cases(): array
    {
        return [
            self::UserDescending,
            self::GameDescending,
            self::ClaimTypeDescending,
            self::SetTypeDescending,
            self::ClaimStatusDescending,
            self::SpecialDescending,
            self::ClaimDateDescending,
            self::FinishedDateDescending,

            self::UserAscending,
            self::GameAscending,
            self::ClaimTypeAscending,
            self::SetTypeAscending,
            self::ClaimStatusAscending,
            self::SpecialAscending,
            self::ClaimDateAscending,
            self::FinishedDateAscending,
        ];
    }

    // need better way of doing this
    public static function toString(int $type, ?int $filter = null): string
    {
        // Extra logic for the finished time as it represents expiration, completion and dropped time.
        if (isset($filter) && ($type == self::FinishedDateDescending || $type == self::FinishedDateAscending)) {
            $activeClaim = ($filter & ClaimFilters::ActiveClaim);
            $completeClaim = ($filter & ClaimFilters::CompleteClaim);
            $droppedClaim = ($filter & ClaimFilters::DroppedClaim);
            $dateText = 'Expiration / Completion / Drop';
            if ($activeClaim && $completeClaim && !$droppedClaim) {
                $dateText = 'Expiration / Completion';
            } elseif ($activeClaim && !$completeClaim && $droppedClaim) {
                $dateText = 'Expiration / Drop';
            } elseif ($activeClaim && !$completeClaim && !$droppedClaim) {
                $dateText = 'Expiration';
            } elseif (!$activeClaim && $completeClaim && $droppedClaim) {
                $dateText = 'Completion / Drop';
            } elseif (!$activeClaim && $completeClaim && !$droppedClaim) {
                $dateText = 'Completion';
            } elseif (!$activeClaim && !$completeClaim && $droppedClaim) {
                $dateText = 'Drop';
            }
            $dateText .= ' Date';

            return $dateText;
        }

        return match ($type % 10) {
            self::UserDescending => "Dev",
            self::GameDescending => "Game",
            self::ClaimTypeDescending => "Claim Type",
            self::SetTypeDescending => "Set Type",
            self::ClaimStatusDescending => "Status",
            self::SpecialDescending => "Special",
            self::ClaimDateDescending => "Claim Date",
            self::FinishedDateDescending => "Expiration Date",
            default => "Invalid sort type",
        };
    }

    public static function getSortingValue(int $input, int $value): int
    {
        return $input === $value ? $value + 10 : $value;
    }
}
