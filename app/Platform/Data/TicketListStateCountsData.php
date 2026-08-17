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
        public int $request,
        public int $resolved,
        public int $quarantined,
        public int $all,
    ) {
    }

    /**
     * @param array{unresolved: int, request: int, resolved: int, quarantined: int, all: int} $counts
     */
    public static function fromCounts(array $counts): self
    {
        return new self(
            unresolved: $counts['unresolved'],
            request: $counts['request'],
            resolved: $counts['resolved'],
            quarantined: $counts['quarantined'],
            all: $counts['all'],
        );
    }
}
