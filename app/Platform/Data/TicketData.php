<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Models\Ticket;
use App\Platform\Enums\TicketableType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('Ticket')]
class TicketData extends Data
{
    public function __construct(
        public int $id,
        public TicketableType $ticketableType,
        public Lazy|int $state,
        public Lazy|AchievementData|LeaderboardData|GameData $ticketable,
    ) {
    }

    public static function fromTicket(Ticket $ticket): self
    {
        return new self(
            id: $ticket->id,
            ticketableType: TicketableType::Achievement,
            state: Lazy::create(fn () => $ticket->state),
            ticketable: Lazy::create(fn () => AchievementData::fromAchievement($ticket->achievement))
        );
    }
}
