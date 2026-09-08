<?php

declare(strict_types=1);

namespace App\Platform\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('TicketListStateCounts')]
class TicketListStateCountsData extends Data
{
    public function __construct(
        public int $unresolved,
        public int $open,
        public int $request,
        public int $resolved,
        public int $closed,
        public int $quarantined,
        public int $all,
    ) {
    }

    /**
     * @param array{unresolved: int, open: int, request: int, resolved: int, closed: int, quarantined: int, all: int} $counts
     */
    public static function fromCounts(array $counts): self
    {
        return new self(
            unresolved: $counts['unresolved'],
            open: $counts['open'],
            request: $counts['request'],
            resolved: $counts['resolved'],
            closed: $counts['closed'],
            quarantined: $counts['quarantined'],
            all: $counts['all'],
        );
    }
}
