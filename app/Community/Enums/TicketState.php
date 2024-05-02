<?php

declare(strict_types=1);

namespace App\Community\Enums;

abstract class TicketState
{
    public const Closed = 0;
    public const Open = 1;
    public const Resolved = 2;
    public const Request = 3;

    public const REASON_DEMOTED = 'Demoted';

    public static function toString(int $type): string
    {
        return match ($type) {
            TicketState::Closed => "Closed",
            TicketState::Open => "Open",
            TicketState::Resolved => "Resolved",
            TicketState::Request => "Request",
            default => "Invalid state",
        };
    }

    public static function isOpen(int $type): bool
    {
        return match ($type) {
            TicketState::Open => true,
            TicketState::Request => true,
            default => false,
        };
    }
}
