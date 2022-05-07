<?php

namespace RA;

abstract class TicketState
{
    public const Closed = 0;
    public const Open = 1;
    public const Resolved = 2;

    public static function toString(int $type): string
    {
        return match ($type) {
            TicketState::Closed => "Closed",
            TicketState::Open => "Open",
            TicketState::Resolved => "Resolved",
            default => "Invalid state",
        };
    }
}
