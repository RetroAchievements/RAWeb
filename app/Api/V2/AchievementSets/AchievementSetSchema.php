<?php

declare(strict_types=1);

namespace App\Api\V2\AchievementSets;

use App\Models\AchievementSet;
use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\ArrayList;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsToMany;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class AchievementSetSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = AchievementSet::class;

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
        return 'achievement-sets';
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),

            Str::make('title')->readOnly(),

            Number::make('pointsTotal', 'points_total')->readOnly(),
            Number::make('pointsWeighted', 'points_weighted')->readOnly(),

            Number::make('achievementsPublished', 'achievements_published')->readOnly(),
            Number::make('achievementsUnpublished', 'achievements_unpublished')->readOnly(),

            Str::make('badgeUrl')->readOnly(),

            DateTime::make('achievementsFirstPublishedAt', 'achievements_first_published_at')->readOnly(),

            ArrayList::make('types')->readOnly(),

            DateTime::make('createdAt', 'created_at')->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->readOnly(),

            BelongsToMany::make('games', 'linkedGames')->type('games')->readOnly(),
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
     * Build an index query for this resource.
     * Only returns achievement sets that are attached to at least one game.
     *
     * @param Builder<AchievementSet> $query
     * @return Builder<AchievementSet>
     */
    public function indexQuery(?object $model, Builder $query): Builder
    {
        return $query->whereHas('gameAchievementSets');
    }
}
