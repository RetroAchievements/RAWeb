<?php

declare(strict_types=1);

namespace App\Api\V2\AchievementSetVersions;

use App\Models\AchievementSetVersion;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class AchievementSetVersionSchema extends Schema
{

    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = AchievementSetVersion::class;

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
        return 'achievement-set-versions';
    }

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            ID::make(),

            Number::make('version')->sortable()->readOnly(),

            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),

            ArrayHash::make('definition', 'definition')->readOnly(),
            Number::make('playersTotal', 'players_total')->sortable()->readOnly(),
            Number::make('playersHardcore', 'players_hardcore')->sortable()->readOnly(),
            Number::make('achievementsPublished', 'achievements_published')->sortable()->readOnly(),
            Number::make('achievementsUnpublished', 'achievements_unpublished')->sortable()->readOnly(),

            BelongsTo::make('achievementSet')->type('achievementSets')->readOnly(),
        ];
    }

    /**
     * Get the resource filters.
     *
     * @return array
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
}
