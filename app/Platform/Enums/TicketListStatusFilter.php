<?php

declare(strict_types=1);

namespace App\Platform\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum TicketListStatusFilter: string
{
    case All = 'all';
    case Unresolved = 'unresolved';
    case Request = 'request';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Quarantined = 'quarantined';

    /**
     * @return 'all'|'unresolved'|'request'|'resolved'|'closed'|'quarantined'
     */
    public function stateCountsBucket(): string
    {
        return $this->value;
    }

    /**
     * @param array{unresolved: int, request: int, resolved: int, closed: int, quarantined: int, all: int} $stateCounts
     */
    public function filteredTotal(array $stateCounts): int
    {
        return $stateCounts[$this->stateCountsBucket()];
    }
}
