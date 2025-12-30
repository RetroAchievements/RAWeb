<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Community\Enums\TriggerTicketState;
use App\Models\TriggerTicket;
use App\Platform\Enums\TicketableType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('TriggerTicket')]
class TriggerTicketData extends Data
{
    public function __construct(
        public int $id,
        public TicketableType $ticketableType,
        public Lazy|TriggerTicketState $state,
        public Lazy|AchievementData|LeaderboardData|GameData $ticketable,
    ) {
    }

    public static function fromTriggerTicket(TriggerTicket $ticket): self
    {
        return new self(
            id: $ticket->id,
            ticketableType: TicketableType::Achievement,
            state: Lazy::create(fn () => $ticket->state),
            ticketable: Lazy::create(fn () => AchievementData::fromAchievement($ticket->achievement))
        );
    }
}
