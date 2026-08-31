<?php

declare(strict_types=1);

namespace App\Platform\Actions\Concerns;

use App\Models\Achievement;
use App\Models\Leaderboard;
use App\Models\Ticket;
use App\Platform\Data\TicketListEntryData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;

trait LoadsTicketListEntryRelations
{
    /**
     * Loads every relation the ticket list row DTO reads.
     *
     * @param Builder<Ticket> $query
     */
    protected function applyTicketListEntryEagerLoads(Builder $query): void
    {
        $query->with([
            'ticketable' => function (Relation $relation) {
                if ($relation instanceof MorphTo) {
                    $relation->morphWith([
                        Achievement::class => ['game.system'],
                        Leaderboard::class => ['game.system'],
                    ]);
                }
            },
            'author',
            'reporter',
            'resolver',
            'emulator',
            'gameHash',
        ]);
    }

    /**
     * @param Builder<Ticket> $query
     * @return TicketListEntryData[]
     */
    protected function fetchTicketListEntries(Builder $query): array
    {
        return $query->get()
            ->map(fn (Ticket $ticket) => TicketListEntryData::fromTicket($ticket))
            ->all();
    }
}
