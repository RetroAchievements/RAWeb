<?php

declare(strict_types=1);

namespace App\Api\V2\Events;

use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class EventSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = Event::class;

    /**
     * Default pagination parameters when client doesn't provide any.
     * This prevents unbounded result sets.
     */
    protected ?array $defaultPagination = ['number' => 1];

    /**
     * Relationships that should always be eager loaded.
     */
    protected array $with = ['legacyGame', 'achievements'];

    /**
     * Get the resource type.
     */
    public static function type(): string
    {
        return 'events';
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),

            Str::make('title', 'games.title')->readOnly()->sortable(),
            Str::make('sortTitle', 'games.sort_title')->readOnly()->sortable(),
            Str::make('badgeUrl')->readOnly(),
            Str::make('state')->readOnly(),

            Number::make('playersTotal', 'players_total')->readOnly(),
            Number::make('achievementsPublished', 'achievements_published')->readOnly(),

            DateTime::make('activeFrom', 'active_from')->sortable()->readOnly(),
            DateTime::make('activeThrough')->readOnly(),

            HasMany::make('awards')->type('event-awards')->readOnly(),

            // TODO add relations and relationship endpoints
            // - achievements
            // - awards
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
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
     * @param Builder<Event> $query
     * @return Builder<Event>
     */
    public function indexQuery(?object $model, Builder $query): Builder
    {
        return $query
            ->join('games', 'events.legacy_game_id', '=', 'games.id')
            ->select('events.*')
            ->addSelect('games.title as title')
            ->addSelect('games.sort_title as sort_title');
    }
}
