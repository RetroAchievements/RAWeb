<?php

declare(strict_types=1);

namespace App\Api\V2\LeaderboardEntries;

use App\Models\Leaderboard;
use App\Models\LeaderboardEntry;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\ValueFormat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Filters\Scope;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class LeaderboardEntrySchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = LeaderboardEntry::class;

    /**
     * Relationships that should always be eager loaded.
     */
    protected array $with = ['leaderboard.game'];

    /**
     * Default pagination parameters when client doesn't provide any.
     * This prevents unbounded result sets.
     */
    protected ?array $defaultPagination = ['number' => 1];

    /**
     * Get the resource type.
     */
    public static function type(): string
    {
        return 'leaderboard-entries';
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),

            Number::make('score')->sortable()->readOnly(),
            Number::make('rank')->readOnly(),

            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),

            BelongsTo::make('user')->readOnly(),
            BelongsTo::make('leaderboard')->readOnly(),
        ];
    }

    /**
     * Get the allowed include paths.
     */
    public function includePaths(): iterable
    {
        // This is intentionally explicit instead of a naive "depth 2".
        // We want to limit eager loads on this query because it runs hot on the DB.
        return [
            'leaderboard',
            'leaderboard.games',
            'user',
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this)->delimiter(','),
            Scope::make('gameId', 'forGameIds'),
            Scope::make('user', 'forUserIdentifier'),
            Scope::make('maxRank', 'forMaxRank'),
        ];
    }

    /**
     * Get the resource paginator.
     */
    public function pagination(): ?Paginator
    {
        return PagePagination::make()
            ->withDefaultPerPage(50);
    }

    /**
     * @param Relation<LeaderboardEntry, Leaderboard|User, mixed> $query
     * @return Relation<LeaderboardEntry, Leaderboard|User, mixed>
     */
    public function relatableQuery(?Request $request, Relation $query): Relation
    {
        $parent = $query->getParent();

        if ($parent instanceof User) {
            return $this->applyUserRelatableQuery($query, $parent);
        }

        /** @var Leaderboard $leaderboard */
        $leaderboard = $parent;

        $isHiddenLeaderboard = $leaderboard->order_column < 0;
        $isFromExcludedSystem = in_array($leaderboard->game->system_id, System::getNonGameSystems(), true);

        if ($isHiddenLeaderboard || $isFromExcludedSystem) {
            $query->whereRaw('1 = 0');

            return $query;
        }

        $direction = $leaderboard->rank_asc ? 'asc' : 'desc';
        if ($leaderboard->format === ValueFormat::ValueUnsigned) {
            $query->orderByRaw(toUnsignedStatement('score') . ' ' . $direction);
        } else {
            $query->orderBy('score', $direction);
        }
        $query->orderBy('updated_at');

        return $query;
    }

    /**
     * When accessed as a User relationship, each entry belongs to a different
     * leaderboard, so rank is computed per entry with a correlated subquery
     * (mirroring V1's calculated_rank) rather than derived from page position.
     * The subquery leans on the (leaderboard_id, deleted_at, score) index.
     *
     * @param Relation<LeaderboardEntry, Leaderboard|User, mixed> $query
     * @return Relation<LeaderboardEntry, Leaderboard|User, mixed>
     */
    private function applyUserRelatableQuery(Relation $query, User $user): Relation
    {
        // Unranked users have no visible leaderboard standing.
        if ($user->unranked_at !== null) {
            $query->whereRaw('1 = 0');

            return $query;
        }

        $query
            ->select('leaderboard_entries.*')
            ->addSelect([
                'rank' => LeaderboardEntry::rankSubquery(),
            ])
            ->whereHas('leaderboard', function (Builder $q) {
                $q->where('order_column', '>=', 0)
                    ->whereHas('game', function (Builder $gameQuery) {
                        $gameQuery->whereNotIn('system_id', [System::Hubs, System::Events]);
                    });
            })
            ->orderBy('leaderboard_id');

        return $query;
    }
}
