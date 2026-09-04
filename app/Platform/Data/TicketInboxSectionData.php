<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Platform\Enums\TicketInboxSectionKind;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('TicketInboxSection')]
class TicketInboxSectionData extends Data
{
    /**
     * @param TicketListEntryData[] $tickets
     */
    public function __construct(
        public TicketInboxSectionKind $kind,
        public int $count,
        public array $tickets,
    ) {
    }
}
