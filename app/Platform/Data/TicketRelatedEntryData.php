<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Community\Enums\TicketResolution;
use App\Community\Enums\TicketState;
use App\Data\UserData;
use App\Models\Ticket;
use Carbon\Carbon;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A "related entry" is one of: another ticket filed against the same ticketable,
 * another ticket by the same reporter on the same game, or a ticket from a
 * different reporter on the same game.
 */
#[TypeScript('TicketRelatedEntry')]
class TicketRelatedEntryData extends Data
{
    public function __construct(
        public int $id,
        public TicketState $state,
        public ?TicketResolution $resolution,
        public Carbon $createdAt,
        public string $ticketableTitle,
        public Lazy|UserData|null $reporter = null,
    ) {
    }

    public static function fromTicket(Ticket $ticketModel, string $ticketableTitle): self
    {
        return new self(
            id: $ticketModel->id,
            state: $ticketModel->state,
            resolution: $ticketModel->resolution,
            createdAt: Carbon::parse($ticketModel->created_at),
            ticketableTitle: $ticketableTitle,
            reporter: Lazy::create(fn () => $ticketModel->reporter ? UserData::fromUser($ticketModel->reporter) : null),
        );
    }
}
