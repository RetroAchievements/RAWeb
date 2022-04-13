<?php

namespace RA\Enums;

abstract class TicketTypes
{
    public const TRIGGERED_AT_THE_WRONG_TIME = 1;
    public const DOES_NOT_TRIGGER = 2;

    public static function renderType(int $type): string
    {
        return match ($type) {
            TicketTypes::DOES_NOT_TRIGGER => "Does not trigger",
            TicketTypes::TRIGGERED_AT_THE_WRONG_TIME => "Triggered at the wrong time",
            default => "Invalid ticket type",
        };
    }
}
