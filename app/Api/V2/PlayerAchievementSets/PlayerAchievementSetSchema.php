<?php

declare(strict_types=1);

namespace App\Api\V2\PlayerAchievementSets;

use App\Models\PlayerAchievementSet;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasOneThrough;
use LaravelJsonApi\Eloquent\Filters\Scope;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class PlayerAchievementSetSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = PlayerAchievementSet::class;

    /**
     * Relationships that should always be eager loaded.
     */
    protected array $with = ['achievementSet.gameAchievementSets'];

    /**
     * Default pagination parameters when client doesn't provide any.
     * This prevents unbounded result sets.
     */
    protected ?array $defaultPagination = ['number' => 1];

    /**
     * Default sort order when client doesn't provide any.
     * Shows sets with most recently unlocked achievements first.
     */
    protected $defaultSort = '-lastUnlockAt';

    /**
     * Get the resource type.
     */
    public static function type(): string
    {
        return 'player-achievement-sets';
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),

            Number::make('achievementsUnlocked', 'achievements_unlocked')->readOnly(),
            Number::make('achievementsUnlockedHardcore', 'achievements_unlocked_hardcore')->readOnly(),
            Number::make('achievementsUnlockedSoftcore', 'achievements_unlocked_softcore')->readOnly(),

            Number::make('points', 'points')->sortable()->readOnly(),
            Number::make('pointsHardcore', 'points_hardcore')->sortable()->readOnly(),
            Number::make('pointsWeighted', 'points_weighted')->sortable()->readOnly(),

            Number::make('completionPercentage', 'completion_percentage')->sortable()->readOnly(),
            Number::make('completionPercentageHardcore', 'completion_percentage_hardcore')->sortable()->readOnly(),

            DateTime::make('lastUnlockAt', 'last_unlock_at')->sortable()->readOnly(),
            DateTime::make('lastUnlockHardcoreAt', 'last_unlock_hardcore_at')->sortable()->readOnly(),
            DateTime::make('completedAt', 'completed_at')->sortable()->readOnly(),
            DateTime::make('completedHardcoreAt', 'completed_hardcore_at')->sortable()->readOnly(),

            Number::make('timeTaken', 'time_taken')->sortable()->readOnly(),
            Number::make('timeTakenHardcore', 'time_taken_hardcore')->sortable()->readOnly(),

            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->readOnly(),

            BelongsTo::make('achievementSet')->type('achievement-sets')->readOnly(),
            HasOneThrough::make('game')->type('games'), // HasOneThrough is always implicitly ->readOnly()
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
            Scope::make('gameId', 'forGameId'),
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
