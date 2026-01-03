<?php

declare(strict_types=1);

namespace App\Api\V2\Achievements;

use App\Models\Achievement;
use App\Models\System;
use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Relations\HasOne;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Scope;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class AchievementSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = Achievement::class;

    /**
     * Relationships that should always be eager loaded.
     */
    protected array $with = [
        'achievementSet.gameAchievementSets',
    ];

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
        return 'achievements';
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

            Number::make('points')->sortable()->readOnly(),
            Number::make('pointsWeighted', 'points_weighted')->sortable()->readOnly(),

            Str::make('badgeUrl')->readOnly(),
            Str::make('badgeLockedUrl')->readOnly(),

            Str::make('type')->readOnly(),

            Boolean::make('isPromoted', 'is_promoted')->readOnly(),

            Number::make('orderColumn')->readOnly(),

            Number::make('unlocksTotal', 'unlocks_total')->readOnly(),
            Number::make('unlocksHardcore', 'unlocks_hardcore')->readOnly(),
            Number::make('unlockPercentage', 'unlock_percentage')->readOnly(),
            Number::make('unlockHardcorePercentage', 'unlock_hardcore_percentage')->readOnly(),

            DateTime::make('createdAt', 'created_at')->readOnly(),
            DateTime::make('modifiedAt', 'modified_at')->readOnly(),

            BelongsTo::make('developer')->type('users')->readOnly(),

            HasOne::make('achievementSet')->type('achievement-sets')->readOnly(),
            HasMany::make('games')->type('games')->readOnly(),

            // TODO add relationships
            // - activeMaintainer (HasOne AchievementMaintainer)
            // - playerAchievements (HasMany PlayerAchievement)

            // TODO implement relationship endpoints to enable links
            // - /achievements/{id}/achievementSet
            // - /achievements/{id}/games
            // - /achievements/{id}/developer
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Scope::make('isPromoted', 'withPromotedStatus'),
            Scope::make('gameId', 'forGame'),
            Where::make('type'),
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
     * Excludes achievements from Hub games (system_id=100) and Event games (system_id=101).
     * Defaults to promoted achievements only unless isPromoted filter is explicitly set.
     *
     * @param Builder<Achievement> $query
     * @return Builder<Achievement>
     */
    public function indexQuery(?object $model, Builder $query): Builder
    {
        // Exclude hub and event game achievements via the game relationship.
        $query->whereHas('game', function (Builder $gameQuery) {
            $gameQuery->where('system_id', '!=', System::Hubs)
                ->where('system_id', '!=', System::Events);
        });

        // Default to promoted only if no isPromoted filter is applied.
        // The filter will override this if explicitly set.
        if (!request()->has('filter.isPromoted')) {
            $query->where('is_promoted', true);
        }

        return $query;
    }
}
