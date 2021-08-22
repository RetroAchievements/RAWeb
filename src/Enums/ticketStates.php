<?php

namespace RA\Enums;

abstract class TicketStates
{
    public const Closed = 0;
    public const Open = 1;
    public const Resolved = 2;

    public static function RenderState(int $type): string
    {
        switch ($type) {
            case TicketStates::Closed:
                return "Closed";
            case TicketStates::Open:
                return "Open";
            case TicketStates::Resolved:
                return "Resolved";
            default:
                return "Invalid state";
        }
    }
}
