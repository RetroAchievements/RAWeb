<?php

namespace RA\Enums;

abstract class TicketStates
{
    public const CLOSED = 0;
    public const OPEN = 1;
    public const RESOLVED = 2;

    public static function renderState(int $type): string
    {
        switch ($type) {
            case TicketStates::CLOSED:
                return "Closed";
            case TicketStates::OPEN:
                return "Open";
            case TicketStates::RESOLVED:
                return "Resolved";
            default:
                return "Invalid state";
        }
    }
}
