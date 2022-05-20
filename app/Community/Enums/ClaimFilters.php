<?php

declare(strict_types=1);

namespace App\Community\Enums;

abstract class ClaimFilters
{
    public const PrimaryClaim = 1 << 0;

    public const CollaborationClaim = 1 << 1;

    public const NewSetClaim = 1 << 2;

    public const RevisionClaim = 1 << 3;

    public const ActiveClaim = 1 << 4;

    public const CompleteClaim = 1 << 5;

    public const DroppedClaim = 1 << 6;

    public const SpecialNone = 1 << 7;

    public const SpecialOwnRevision = 1 << 8;

    public const SpecialFreeRollout = 1 << 9;

    public const SpecialScheduledRelease = 1 << 10;

    public const DeveloperClaim = 1 << 11;

    public const JuniorDeveloperClaim = 1 << 12;

    // This should be updated every time a new filter is added so it has all possible filter bits set
    public const AllFilters = (1 << 13) - 1;

    // Filter to show all Complete Primary claims
    public const AllCompletedPrimaryClaims = self::AllFilters & ~self::ActiveClaim & ~self::DroppedClaim & ~self::CollaborationClaim;

    // Default filter is everything except Complete and Dropped claims
    public const AllActiveClaims = self::AllFilters & ~self::CompleteClaim & ~self::DroppedClaim;

    public const AllDroppedClaims = self::AllFilters & ~self::CompleteClaim & ~self::ActiveClaim;

    public static function cases(): array
    {
        return [
            self::PrimaryClaim,
            self::CollaborationClaim,
            self::NewSetClaim,
            self::RevisionClaim,
            self::ActiveClaim,
            self::CompleteClaim,
            self::DroppedClaim,
            self::SpecialNone,
            self::SpecialOwnRevision,
            self::SpecialFreeRollout,
            self::SpecialScheduledRelease,
            self::DeveloperClaim,
            self::JuniorDeveloperClaim,
            self::AllFilters,
            self::AllCompletedPrimaryClaims,
            self::AllActiveClaims,
            self::AllDroppedClaims,
        ];
    }
}
