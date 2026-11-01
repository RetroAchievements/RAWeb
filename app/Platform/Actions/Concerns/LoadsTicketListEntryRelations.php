<?php

declare(strict_types=1);

namespace App\Platform\Actions\Concerns;

use App\Models\Achievement;
use App\Models\Leaderboard;
use App\Models\Ticket;
use App\Models\TicketVerdictAccuracyResult;
use App\Models\User;
use App\Platform\Actions\BuildTicketSignalsAction;
use App\Platform\Data\TicketListEntryData;
use App\Platform\Data\TicketSignalsData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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
     * Builds the triage signals for every ticket in the collection, then
     * attaches the historical hit rate of each verdict to its signals.
     *
     * @param Collection<int, Ticket> $tickets
     * @return array<int, TicketSignalsData|null>|null null when the viewer may not see signals
     */
    protected function resolveTicketSignalsForViewer(Collection $tickets, ?User $viewer): ?array
    {
        if (!$viewer?->can('viewTriageSignals', Ticket::class)) {
            return null;
        }

        $signalsByTicketId = (new BuildTicketSignalsAction())->execute($tickets);

        $shownAccuracyByVerdict = TicketVerdictAccuracyResult::findShownAccuracyByVerdict();
        foreach ($signalsByTicketId as $signals) {
            if ($signals === null) {
                continue;
            }

            $signals->accuracy = $shownAccuracyByVerdict[$signals->verdict->value] ?? null;
        }

        return $signalsByTicketId;
    }

    /**
     * @param Collection<int, Ticket> $tickets
     * @param array<int, TicketSignalsData|null>|null $signalsForTickets
     * @return TicketListEntryData[]
     */
    protected function resolveTicketListEntries(Collection $tickets, ?array $signalsForTickets): array
    {
        return $tickets
            ->map(function (Ticket $ticket) use ($signalsForTickets) {
                if ($signalsForTickets === null) {
                    return TicketListEntryData::fromTicket($ticket);
                }

                return TicketListEntryData::fromTicket($ticket, $signalsForTickets[$ticket->id] ?? null)
                    ->include('signals');
            })
            ->all();
    }
}
