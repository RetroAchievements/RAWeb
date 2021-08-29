<?php

namespace RA\Enums;

abstract class TicketTypes
{
    public const TRIGGERED_AT_THE_WRONG_TIME = 1;
    public const DOES_NOT_TRIGGER = 2;

    public static function renderType(int $type): string
    {
        switch ($type) {
            case TicketTypes::DOES_NOT_TRIGGER:
                return "Does not trigger";
            case TicketTypes::TRIGGERED_AT_THE_WRONG_TIME:
                return "Triggered at the wrong time";
            default:
                return "Invalid ticket type";
        }
    }
}
