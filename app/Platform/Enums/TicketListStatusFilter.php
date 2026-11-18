<?php

declare(strict_types=1);

namespace App\Platform\Enums;

use App\Community\Enums\TicketState;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum TicketListStatusFilter: string
{
    case All = 'all';
    case Unresolved = 'unresolved';
    case Open = 'open';
    case Request = 'request';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Quarantined = 'quarantined';

    /**
     * Which states this filter shows when selected.
     * If the value is null, every state is shown.
     *
     * @return TicketState[]|null
     */
    public function states(): ?array
    {
        return match ($this) {
            self::All => null,
            self::Unresolved => [TicketState::Open, TicketState::Request],
            self::Open => [TicketState::Open],
            self::Request => [TicketState::Request],
            self::Resolved => [TicketState::Resolved],
            self::Closed => [TicketState::Closed],
            self::Quarantined => [TicketState::Quarantined],
        };
    }

    /**
     * @return 'all'|'unresolved'|'open'|'request'|'resolved'|'closed'|'quarantined'
     */
    public function stateCountsBucket(): string
    {
        return $this->value;
    }

    /**
     * @param array{unresolved: int, open: int, request: int, resolved: int, closed: int, quarantined: int, all: int} $stateCounts
     */
    public function filteredTotal(array $stateCounts): int
    {
        return $stateCounts[$this->stateCountsBucket()];
    }
}
