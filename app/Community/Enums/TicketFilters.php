<?php

declare(strict_types=1);

namespace App\Community\Enums;

abstract class TicketFilters
{
    public const StateOpen = 1 << 0;

    public const StateClosed = 1 << 1;

    public const StateResolved = 1 << 2;

    public const TypeTriggeredAtWrongTime = 1 << 3;

    public const TypeDidNotTrigger = 1 << 4;

    public const HashKnown = 1 << 5;

    public const HashUnknown = 1 << 6;

    public const EmulatorRA = 1 << 7;

    public const EmulatorRetroArchCoreSpecified = 1 << 8;

    public const EmulatorRetroArchCoreNotSpecified = 1 << 9;

    public const EmulatorUnknown = 1 << 10;

    public const HardcoreUnknown = 1 << 11;

    public const HardcoreOn = 1 << 12;

    public const HardcoreOff = 1 << 13;

    public const DevInactive = 1 << 14;

    public const DevActive = 1 << 15;

    public const DevJunior = 1 << 16;

    public const ResolvedByNonAuthor = 1 << 17;

    public const StateRequest = 1 << 18;

    public const EmulatorOther = 1 << 19;

    public const ResolvedByNonReporter = 1 << 20;

    public const ProgressionOnly = 1 << 21;

    // This should updated every time a new filter is added so it has all possible filter bits set
    public const AllFilters = (1 << 22) - 1;

    // All filter is everything except Not Author (Not Author filter excludes items) and Progression only
    public const All = self::AllFilters & ~self::ResolvedByNonAuthor
                                        & ~self::ProgressionOnly;

    // Default filter is everything except Closed, Resolved, Not Author and Progression only
    public const Default = self::AllFilters & ~self::StateClosed
                                            & ~self::StateResolved
                                            & ~self::ResolvedByNonAuthor
                                            & ~self::ResolvedByNonReporter
                                            & ~self::ProgressionOnly;
}
