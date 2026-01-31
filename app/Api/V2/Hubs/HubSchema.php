<?php

declare(strict_types=1);

namespace App\Api\V2\Hubs;

use App\Models\GameSet;
use App\Platform\Enums\GameSetType;
use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsToMany;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Scope;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\Sorting\SortWithCount;

class HubSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = GameSet::class;

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
        return 'hubs';
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),

            Str::make('title')->sortable()->readOnly(),
            Str::make('sortTitle', 'sort_title')->sortable()->readOnly(),
            Str::make('badgeUrl')->readOnly(),

            Boolean::make('hasMatureContent', 'has_mature_content')->readOnly(),

            Number::make('gamesCount')->readOnly(),
            Number::make('linkedHubsCount')->readOnly(),
            Boolean::make('isEventHub')->readOnly(),

            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),

            // These relationships are available via paginated endpoints:
            // - /hubs/{id}/games
            // - /hubs/{id}/links (linked hubs)
            // cannotEagerLoad() prevents ?include= on index/show, forcing clients to use the paginated relationship endpoints.
            BelongsToMany::make('games')->cannotEagerLoad()->readOnly(),
            BelongsToMany::make('links', 'linkedHubs')->type('hubs')->cannotEagerLoad()->readOnly(),
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Scope::make('parentId', 'withParentId'),
            Scope::make('title', 'titleContains'),
        ];
    }

    /**
     * Get the sortable fields for this resource.
     */
    public function sortables(): iterable
    {
        return [
            SortWithCount::make('games', 'gamesCount'),
            SortWithCount::make('linkedHubs', 'linkedHubsCount')->countAs('linked_hubs_count'),
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
     * Only includes hubs (type=hub), excludes SimilarGames.
     *
     * @param Builder<GameSet> $query
     * @return Builder<GameSet>
     */
    public function indexQuery(?object $model, Builder $query): Builder
    {
        return $query
            ->where('type', GameSetType::Hub)
            ->withCount(['games', 'linkedHubs as linked_hubs_count']);
    }
}
