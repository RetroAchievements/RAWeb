<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Community\Enums\TicketResolution;
use App\Community\Enums\TicketState;
use App\Models\Ticket;
use Carbon\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A "related entry" is another ticket filed against the same ticketable.
 */
#[TypeScript('TicketRelatedEntry')]
class TicketRelatedEntryData extends Data
{
    public function __construct(
        public int $id,
        public TicketState $state,
        public ?TicketResolution $resolution,
        public Carbon $createdAt,
    ) {
    }

    public static function fromTicket(Ticket $ticketModel): self
    {
        return new self(
            id: $ticketModel->id,
            state: $ticketModel->state,
            resolution: $ticketModel->resolution,
            createdAt: Carbon::parse($ticketModel->created_at),
        );
    }
}
