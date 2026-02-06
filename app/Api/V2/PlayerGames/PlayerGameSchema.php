<?php

declare(strict_types=1);

namespace App\Api\V2\PlayerGames;

use App\Models\PlayerGame;
use App\Models\System;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class PlayerGameSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = PlayerGame::class;

    /**
     * Relationships that should always be eager loaded.
     * We always need the game to exclude hubs/events.
     */
    protected array $with = ['game'];

    /**
     * Default pagination parameters when client doesn't provide any.
     * This prevents unbounded result sets.
     */
    protected ?array $defaultPagination = ['number' => 1];

    /**
     * Default sort order when client doesn't provide any.
     * Shows most recently played games first.
     */
    protected $defaultSort = '-lastPlayedAt';

    /**
     * Get the resource type.
     */
    public static function type(): string
    {
        return 'player-games';
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),

            // Core set fields (non-all columns).
            Number::make('coreAchievementsTotal', 'achievements_total')->readOnly(),
            Number::make('coreAchievementsUnlocked', 'achievements_unlocked')->readOnly(),
            Number::make('coreAchievementsUnlockedHardcore', 'achievements_unlocked_hardcore')->readOnly(),
            Number::make('coreAchievementsUnlockedSoftcore', 'achievements_unlocked_softcore')->readOnly(),
            Number::make('corePointsTotal', 'points_total')->readOnly(),
            Number::make('corePoints', 'points')->readOnly(),
            Number::make('corePointsHardcore', 'points_hardcore')->readOnly(),
            Number::make('corePointsWeighted', 'points_weighted')->readOnly(),

            // All sets fields (all_* columns, exposed as unprefixed names).
            Number::make('achievementsTotal', 'all_achievements_total')->readOnly(),
            Number::make('achievementsUnlocked', 'all_achievements_unlocked')->readOnly(),
            Number::make('achievementsUnlockedHardcore', 'all_achievements_unlocked_hardcore')->readOnly(),
            Number::make('pointsTotal', 'all_points_total')->readOnly(),
            Number::make('points', 'all_points')->readOnly(),
            Number::make('pointsHardcore', 'all_points_hardcore')->readOnly(),
            Number::make('pointsWeighted', 'all_points_weighted')->readOnly(),

            // Completion.
            Number::make('completionPercentage', 'completion_percentage')->readOnly(),
            Number::make('completionPercentageHardcore', 'completion_percentage_hardcore')->readOnly(),

            // Timestamps.
            DateTime::make('lastPlayedAt', 'last_played_at')->sortable()->readOnly(),
            DateTime::make('firstUnlockAt', 'first_unlock_at')->readOnly(),
            DateTime::make('lastUnlockAt', 'last_unlock_at')->readOnly(),
            DateTime::make('lastUnlockHardcoreAt', 'last_unlock_hardcore_at')->readOnly(),

            // Milestones.
            DateTime::make('beatenAt', 'beaten_at')->readOnly(),
            DateTime::make('beatenHardcoreAt', 'beaten_hardcore_at')->readOnly(),
            DateTime::make('coreCompletedAt', 'completed_at')->readOnly(),
            DateTime::make('coreCompletedHardcoreAt', 'completed_hardcore_at')->readOnly(),

            // Time tracking.
            Number::make('playtimeTotal', 'playtime_total')->readOnly(),
            Number::make('timeToBeat', 'time_to_beat')->readOnly(),
            Number::make('timeToBeatHardcore', 'time_to_beat_hardcore')->readOnly(),
            Number::make('timeTaken', 'time_taken')->readOnly(),

            // Relationships.
            BelongsTo::make('game')->readOnly(),
            BelongsTo::make('user')->readOnly(),
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
     * Excludes hub and event games so they don't appear in player library listings.
     *
     * @param Builder<PlayerGame> $query
     * @return Builder<PlayerGame>
     */
    public function indexQuery(?object $model, Builder $query): Builder
    {
        return $this->excludeHubsAndEvents($query);
    }

    /**
     * When accessed as a User relationship, apply the same hub/event exclusion.
     *
     * @param Relation<PlayerGame, User, mixed> $query
     * @return Relation<PlayerGame, User, mixed>
     */
    public function relatableQuery(?Request $request, Relation $query): Relation
    {
        return $this->excludeHubsAndEvents($query);
    }

    /**
     * Both index and relatable queries need to exclude hubs and events.
     *
     * @template T of Builder<PlayerGame>|Relation<PlayerGame, User, mixed>
     *
     * @param T $query
     * @return T
     */
    private function excludeHubsAndEvents(Builder|Relation $query): Builder|Relation
    {
        return $query->whereHas('game', function ($gameQuery) {
            $gameQuery->whereNotIn('system_id', [System::Hubs, System::Events]);
        });
    }
}
