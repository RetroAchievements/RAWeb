<?php

declare(strict_types=1);

namespace App\Community\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum TicketResolution: string
{
    case Fixed = 'fixed';
    case MistakenReport = 'mistaken_report';
    case NotEnoughInformation = 'not_enough_information';
    case WrongRom = 'wrong_rom';
    case NetworkProblems = 'network_problems';
    case UnableToReproduce = 'unable_to_reproduce';
    case UnableToDebug = 'unable_to_debug';
    case Demoted = 'demoted';
    case Other = 'other';

    /**
     * The state a ticket moves to when it finishes with this resolution.
     */
    public function finishedState(): TicketState
    {
        return $this === self::Fixed ? TicketState::Resolved : TicketState::Closed;
    }

    /**
     * The reason text that closing comments contain in their 'Reason: "..."' string.
     */
    public function closeReasonText(): ?string
    {
        return match ($this) {
            self::Fixed => null,
            self::MistakenReport => 'Mistaken report',
            self::NotEnoughInformation => 'Not enough information',
            self::WrongRom => 'Wrong ROM',
            self::NetworkProblems => 'Network problems',
            self::UnableToReproduce => 'Unable to reproduce',
            self::UnableToDebug => 'Unable to debug due to no toolkit support',
            self::Demoted => 'Demoted',
            self::Other => 'See the comments',
        };
    }

    /**
     * Builds the Server comment written when a ticket closes.
     */
    public function closeCommentBody(string $closerName): string
    {
        return "Ticket closed by {$closerName}. Reason: \"{$this->closeReasonText()}\".";
    }
}
