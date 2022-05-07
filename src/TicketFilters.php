<?php

namespace RA;

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

    public const All = (1 << 18) - 1;

    // Default filter is everything except Closed, Resolved, and Not Author
    public const Default = self::All & ~self::StateClosed
                                     & ~self::StateResolved
                                     & ~self::ResolvedByNonAuthor;
}
