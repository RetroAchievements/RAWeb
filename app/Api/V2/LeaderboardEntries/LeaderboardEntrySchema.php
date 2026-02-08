<?php

declare(strict_types=1);

namespace App\Api\V2\LeaderboardEntries;

use App\Models\Leaderboard;
use App\Models\LeaderboardEntry;
use App\Models\System;
use App\Platform\Enums\ValueFormat;
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
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Scope::make('user', 'forUserIdentifier'),
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
     * @param Relation<LeaderboardEntry, Leaderboard, mixed> $query
     * @return Relation<LeaderboardEntry, Leaderboard, mixed>
     */
    public function relatableQuery(?Request $request, Relation $query): Relation
    {
        /** @var Leaderboard $leaderboard */
        $leaderboard = $query->getParent();

        $isHiddenLeaderboard = $leaderboard->order_column < 0;
        $isFromExcludedSystem = in_array($leaderboard->game->system_id, [System::Hubs, System::Events], true);

        if ($isHiddenLeaderboard || $isFromExcludedSystem) {
            $query->whereRaw('1 = 0');

            return $query;
        }

        $direction = $leaderboard->rank_asc ? 'ASC' : 'DESC';
        if ($leaderboard->format === ValueFormat::ValueUnsigned) {
            $query->orderByRaw(toUnsignedStatement('score') . ' ' . $direction);
        } else {
            $query->orderBy('score', $direction);
        }
        $query->orderBy('updated_at');

        return $query;
    }
}
