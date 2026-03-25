<?php

declare(strict_types=1);

namespace App\Api\V2\PlayerAchievements;

use App\Models\Achievement;
use App\Models\PlayerAchievement;
use App\Models\System;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasOneThrough;
use LaravelJsonApi\Eloquent\Filters\Scope;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class PlayerAchievementSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = PlayerAchievement::class;

    /**
     * Relationships that should always be eager loaded.
     * We always need the achievement's game to exclude hubs.
     */
    protected array $with = ['achievement.game', 'user'];

    /**
     * Default pagination parameters when client doesn't provide any.
     * This prevents unbounded result sets.
     */
    protected ?array $defaultPagination = ['number' => 1];

    /**
     * Default sort order when client doesn't provide any.
     * Shows most recently unlocked achievements first.
     */
    protected $defaultSort = '-unlockedAt';

    /**
     * Get the resource type.
     */
    public static function type(): string
    {
        return 'player-achievements';
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),

            DateTime::make('unlockedAt', 'unlocked_at')->sortable()->readOnly(),
            DateTime::make('unlockedHardcoreAt', 'unlocked_hardcore_at')->sortable()->readOnly(),

            BelongsTo::make('achievement')->readOnly(),
            BelongsTo::make('user')->type('users')->readOnly(),
            HasOneThrough::make('game')->type('games'),
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Scope::make('achievementSetId', 'forAchievementSetId'),
            Scope::make('gameId', 'forGameId'),
            Scope::make('unlockedFrom', 'unlockedFrom'),
            Scope::make('unlockedTo', 'unlockedTo'),
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
     *
     * @param Builder<PlayerAchievement> $query
     * @return Builder<PlayerAchievement>
     */
    public function indexQuery(?object $model, Builder $query): Builder
    {
        return $this->excludeNonGameSystems($query);
    }

    /**
     * @param Relation<PlayerAchievement, User, mixed> $query
     * @return Relation<PlayerAchievement, User, mixed>
     */
    public function relatableQuery(?Request $request, Relation $query): Relation
    {
        $query = $this->excludeNonGameSystems($query);

        // When accessed via /achievements/{id}/player-achievements, exclude unranked users.
        if ($query->getParent() instanceof Achievement) {
            $query->ranked();
        }

        return $query;
    }

    /**
     * @template T of Builder<PlayerAchievement>|Relation<PlayerAchievement, User, mixed>
     *
     * @param T $query
     * @return T
     */
    private function excludeNonGameSystems(Builder|Relation $query): Builder|Relation
    {
        return $query->whereHas('achievement', function ($achievementQuery) {
            $achievementQuery->whereHas('game', function ($gameQuery) {
                $gameQuery->whereNotIn('system_id', System::getNonGameSystems());
            });
        });
    }
}
