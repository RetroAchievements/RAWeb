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
    case Quarantined = 'quarantined';

    /**
     * @return 'all'|'unresolved'|'request'|'resolved'|'quarantined'
     */
    public function stateCountsBucket(): string
    {
        return $this->value;
    }

    /**
     * @param array{unresolved: int, request: int, resolved: int, quarantined: int, all: int} $stateCounts
     */
    public function filteredTotal(array $stateCounts): int
    {
        return $stateCounts[$this->stateCountsBucket()];
    }
}
