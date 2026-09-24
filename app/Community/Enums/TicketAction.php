<?php

declare(strict_types=1);

namespace App\Community\Enums;

enum TicketAction: string
{
    case ClosedMistaken = 'closed-mistaken';
    case Resolved = 'resolved';
    case Demoted = 'demoted';
    case NotEnoughInfo = 'not-enough-info';
    case WrongRom = 'wrong-rom';
    case Network = 'network';
    case UnableToReproduce = 'unable-to-reproduce';
    case UnableToDebug = 'unable-to-debug';
    case ClosedOther = 'closed-other';
    case Request = 'request';
    case Reopen = 'reopen';

    /**
     * When this action sets a ticket to be done or resolved,
     * which resolution gets recorded.
     */
    public function resolution(): ?TicketResolution
    {
        return match ($this) {
            self::ClosedMistaken => TicketResolution::MistakenReport,
            self::Resolved => TicketResolution::Fixed,
            self::Demoted => TicketResolution::Demoted,
            self::NotEnoughInfo => TicketResolution::NotEnoughInformation,
            self::WrongRom => TicketResolution::WrongRom,
            self::Network => TicketResolution::NetworkProblems,
            self::UnableToReproduce => TicketResolution::UnableToReproduce,
            self::UnableToDebug => TicketResolution::UnableToDebug,
            self::ClosedOther => TicketResolution::Other,
            self::Request, self::Reopen => null,
        };
    }

    public function targetState(): TicketState
    {
        return match ($this) {
            self::Request => TicketState::Request,
            self::Reopen => TicketState::Open,
            self::Resolved => TicketState::Resolved,
            default => TicketState::Closed,
        };
    }
}
