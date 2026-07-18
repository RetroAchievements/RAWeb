<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Community\Enums\TicketState;
use App\Models\Achievement;
use App\Models\Leaderboard;
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
            ticketable: Lazy::create(function () use ($ticket) {
                $ticketable = $ticket->ticketable;

                return match (true) {
                    $ticketable instanceof Achievement => AchievementData::fromAchievement($ticketable),
                    $ticketable instanceof Leaderboard => LeaderboardData::fromLeaderboard($ticketable),
                    default => throw new LogicException("Unsupported ticketable for ticket {$ticket->id}."),
                };
            }),
        );
    }
}
