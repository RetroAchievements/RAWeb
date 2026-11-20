<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Platform\Enums\TicketListSortField;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('TicketListGrouping')]
class TicketListGroupingData extends Data
{
    /**
     * @param array<int|string, int> $counts keyed by game ID or "{ticketableType}:{ticketableId}"
     * @param bool $isFirstGroupContinued true when the page's first group started on the previous page
     */
    public function __construct(
        public TicketListSortField $field,
        #[LiteralTypeScriptType('Record<string, number>')]
        public array $counts,
        public bool $isFirstGroupContinued,
    ) {
    }
}
