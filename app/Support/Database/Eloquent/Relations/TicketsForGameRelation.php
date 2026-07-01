<?php

declare(strict_types=1);

namespace App\Support\Database\Eloquent\Relations;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\Ticket;
use App\Platform\Contracts\Ticketable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Polymorphic-via-two-pivots: tickets that belong to a Game through either an
 * Achievement or a Leaderboard ticketable.
 *
 * Eloquent ships no native relation for this shape, so we wrap `whereHasMorph`
 * and resolve `ticketable.game_id` per result at match time.
 *
 * @extends Relation<Ticket, Game, Collection<int, Ticket>>
 */
class TicketsForGameRelation extends Relation
{
    public function __construct(Game $parent)
    {
        parent::__construct(Ticket::query(), $parent);
    }

    public function addConstraints(): void
    {
        if (!static::$constraints) {
            return;
        }

        /** @var Game $parent */
        $parent = $this->parent;

        $this->query->whereHasMorph(
            'ticketable',
            [Achievement::class, Leaderboard::class],
            fn (Builder $q) => $q->where('game_id', $parent->id),
        );
    }

    /**
     * @param array<int, Model> $models
     */
    public function addEagerConstraints(array $models): void
    {
        $gameIds = collect($models)
            ->pluck($this->parent->getKeyName())
            ->unique()
            ->values()
            ->all();

        $this->query->whereHasMorph(
            'ticketable',
            [Achievement::class, Leaderboard::class],
            fn (Builder $q) => $q->whereIn('game_id', $gameIds),
        );
    }

    /**
     * @param array<int, Model> $models
     * @param string $relation
     * @return array<int, Model>
     */
    public function initRelation(array $models, $relation): array
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $this->related->newCollection());
        }

        return $models;
    }

    /**
     * @param array<int, Model> $models
     * @param Collection<int, Ticket> $results
     * @param string $relation
     * @return array<int, Model>
     */
    public function match(array $models, Collection $results, $relation): array
    {
        if ($results->isEmpty() || empty($models)) {
            return $models;
        }

        // Batches one query per morph type so `game_id` resolves in-memory below.
        $results->loadMissing('ticketable');

        /** @var array<int, list<Ticket>> $ticketsByGameId */
        $ticketsByGameId = [];
        foreach ($results as $ticket) {
            $gameId = $this->resolveGameIdFromTicketable($ticket);
            if ($gameId !== null) {
                $ticketsByGameId[$gameId][] = $ticket;
            }
        }

        foreach ($models as $model) {
            $key = $model->getKey();
            if (isset($ticketsByGameId[$key])) {
                $model->setRelation($relation, $this->related->newCollection($ticketsByGameId[$key]));
            }
        }

        return $models;
    }

    public function getResults(): Collection
    {
        return $this->query->get();
    }

    /**
     * `whereHasMorph` guarantees the ticketable implements `Ticketable`. The
     * contract's `getTicketableGameId` returns the FK column directly, avoiding
     * a lazy `Game` load.
     */
    private function resolveGameIdFromTicketable(Ticket $ticket): ?int
    {
        $ticketable = $ticket->ticketable;

        return $ticketable instanceof Ticketable ? $ticketable->getTicketableGameId() : null;
    }
}
