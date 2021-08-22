<?php

namespace RA\Enums;

abstract class TicketTypes
{
    public const TriggeredAtTheWrongTime = 1;
    public const DoesNotTrigger = 2;

    public static function RenderType(int $type): string
    {
        switch ($type) {
            case TicketTypes::DoesNotTrigger:
                return "Does not trigger";
            case TicketTypes::TriggeredAtTheWrongTime:
                return "Triggered at the wrong time";
            default:
                return "Invalid ticket type";
        }
    }
}
