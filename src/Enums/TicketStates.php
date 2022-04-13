<?php

namespace RA\Enums;

abstract class TicketStates
{
    public const CLOSED = 0;
    public const OPEN = 1;
    public const RESOLVED = 2;

    public static function renderState(int $type): string
    {
        return match ($type) {
            TicketStates::CLOSED => "Closed",
            TicketStates::OPEN => "Open",
            TicketStates::RESOLVED => "Resolved",
            default => "Invalid state",
        };
    }
}
