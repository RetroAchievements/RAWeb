<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Community\Enums\TicketState;
use App\Models\Ticket;
use App\Platform\Enums\TicketableType;
use LogicException;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('Ticket')]
class TicketData extends Data
{
    public function __construct(
        public int $id,
        public TicketableType $ticketableType,
        public Lazy|TicketState $state,
        public Lazy|AchievementData|LeaderboardData|GameData $ticketable,
    ) {
    }

    public static function fromTicket(Ticket $ticket): self
    {
        $ticketableType = TicketableType::from($ticket->ticketable_type);

        return new self(
            id: $ticket->id,
            ticketableType: $ticketableType,
            state: Lazy::create(fn () => $ticket->state),
            ticketable: Lazy::create(fn () => match ($ticketableType) {
                TicketableType::Achievement => AchievementData::fromAchievement($ticket->achievement),
                TicketableType::Leaderboard => LeaderboardData::fromLeaderboard($ticket->leaderboard),
                TicketableType::RichPresence => throw new LogicException('Rich presence tickets are not currently supported.'),
            }),
        );
    }
}
