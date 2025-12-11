<?php

declare(strict_types=1);

namespace App\Api\V2\Games;

use App\Models\Game;
use App\Models\System;
use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class GameSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = Game::class;

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
        return 'games';
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),

            Str::make('title', 'Title')->sortable(),
            Str::make('sortTitle', 'sort_title')->sortable(),

            Str::make('badgeUrl')->readOnly(),
            Str::make('imageBoxArtUrl')->readOnly(),
            Str::make('imageTitleUrl')->readOnly(),
            Str::make('imageIngameUrl')->readOnly(),

            Number::make('forumTopicId', 'ForumTopicID'),
            DateTime::make('releasedAt', 'released_at')->sortable(),
            Str::make('releasedAtGranularity', 'released_at_granularity'),

            Number::make('playersTotal', 'players_total')->sortable(),
            Number::make('playersHardcore', 'players_hardcore')->sortable(),

            Number::make('achievementsPublished', 'achievements_published')->sortable(),
            Number::make('achievementsUnpublished', 'achievements_unpublished'),

            Number::make('pointsTotal', 'points_total')->sortable(),
            Number::make('pointsWeighted', 'TotalTruePoints')->sortable(),

            Number::make('timesBeaten', 'times_beaten'),
            Number::make('timesBeatenHardcore', 'times_beaten_hardcore'),
            Number::make('medianTimeToBeatMinutes', 'median_time_to_beat'),
            Number::make('medianTimeToBeatHardcoreMinutes', 'median_time_to_beat_hardcore'),

            BelongsTo::make('system')->readOnly(),
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('systemId', 'ConsoleID'),
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
     * Excludes Hub games (ConsoleID=100) and Event games (ConsoleID=101).
     *
     * @param Builder<Game> $query
     * @return Builder<Game>
     */
    public function indexQuery(?object $model, Builder $query): Builder
    {
        return $query->where('ConsoleID', '!=', System::Hubs)
            ->where('ConsoleID', '!=', System::Events);
    }
}
