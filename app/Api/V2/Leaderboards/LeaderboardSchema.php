<?php

declare(strict_types=1);

namespace App\Api\V2\Leaderboards;

use App\Models\Leaderboard;
use App\Models\System;
use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Scope;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class LeaderboardSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = Leaderboard::class;

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
        return 'leaderboards';
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),

            Str::make('title')->sortable()->readOnly(),
            Str::make('description')->readOnly(),

            Str::make('format')->readOnly(),
            Boolean::make('rankAsc', 'rank_asc')->readOnly(),

            Str::make('state')->readOnly(),
            Number::make('orderColumn', 'order_column')->sortable()->readOnly(),

            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),

            BelongsTo::make('games', 'game')->type('games')->readOnly(),
            BelongsTo::make('developer')->type('users')->readOnly(),

            // TODO implement relationship endpoints to enable links
            // - /leaderboards/{id}/game
            // - /leaderboards/{id}/developer
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('gameId', 'game_id'),
            Scope::make('state', 'withState'),
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
     * Build an index query for this resource.
     * Excludes leaderboards from Hub games (system_id=100) and Event games (system_id=101).
     * Also excludes hidden leaderboards (order_column < 0).
     *
     * @param Builder<Leaderboard> $query
     * @return Builder<Leaderboard>
     */
    public function indexQuery(?object $model, Builder $query): Builder
    {
        return $query
            ->whereHas('game', fn (Builder $gameQuery) => $gameQuery
                ->whereNotIn('system_id', [System::Hubs, System::Events]))
            ->where('order_column', '>=', 0);
    }
}
