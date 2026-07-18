<?php

declare(strict_types=1);

namespace App\Api\V2\AchievementSetVersions;

use App\Models\AchievementSetVersion;
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

class AchievementSetVersionSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = AchievementSetVersion::class;

    protected int $maxDepth = 2;

    /**
     * Default pagination parameters when client doesn't provide any.
     * This prevents unbounded result sets.
     */
    protected ?array $defaultPagination = ['number' => 1];

    /**
     * Default sort order when client doesn't provide any.
     */
    protected $defaultSort = '-createdAt';

    /**
     * Relationships that should always be eager loaded.
     */
    protected array $with = [
        'achievementSet.gameAchievementSets',
    ];

    /**
     * Get the resource type.
     */
    public static function type(): string
    {
        return 'achievement-set-versions';
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),

            Number::make('version')->readOnly(),

            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),
            Number::make('playersTotal', 'players_total')->readOnly(),
            Number::make('playersHardcore', 'players_hardcore')->readOnly(),
            Number::make('achievementsPublished', 'achievements_published')->readOnly(),
            Number::make('achievementsUnpublished', 'achievements_unpublished')->readOnly(),
            Number::make('pointsTotal', 'points_total')->readOnly(),

            Str::make('achievementSetId', 'achievement_set_id')->readOnly(),

            BelongsTo::make('achievementSet')->type('achievement-sets')->readOnly(),
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('achievementSetId', 'achievement_set_id'),
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
