<?php

declare(strict_types=1);

namespace App\Api\V2\Users;

use App\Api\V2\Tickets\TicketableTypeFilter;
use App\Api\V2\Tickets\TicketStateFilter;
use App\Api\V2\Tickets\TicketTypeFilter;
use App\Api\V2\Tickets\UserUlidFilter;
use App\Api\V2\UserAwards\UserAwardGameAwardTierFilter;
use App\Api\V2\UserAwards\UserAwardKindFilter;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\ArrayList;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Scope;
use LaravelJsonApi\Eloquent\Filters\WhereNull;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class UserSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = User::class;

    /**
     * Default pagination parameters when client doesn't provide any.
     * This prevents unbounded result sets.
     */
    protected ?array $defaultPagination = ['number' => 1];

    /**
     * Relationships that should always be eager loaded.
     *
     * The visibleRole and displayableRoles attributes both derive from roles,
     * and lazily loading them costs two queries per user on the index.
     */
    protected array $with = ['roles', 'allTimeGlobalRankings'];

    /**
     * Default sort order when client doesn't provide any.
     * Shows highest point users first for leaderboard-style results.
     */
    protected $defaultSort = '-pointsHardcore';

    /**
     * Get the resource type.
     */
    public static function type(): string
    {
        return 'users';
    }

    /**
     * Create a repository for this resource that supports flexible identifier lookup.
     */
    public function repository(): UserRepository
    {
        return new UserRepository(
            $this,
            $this->driver(),
            $this->parser(),
        );
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            // Use a permissive pattern to accept ULID, display_name, or username.
            // The actual lookup is handled by UserRepository using FindUserByIdentifierAction.
            ID::make('ulid')->matchAs('[^/]+'),

            Str::make('displayName', 'display_name')->readOnly(),

            Str::make('avatarUrl')->readOnly(),
            Str::make('motto')->readOnly(),

            Number::make('points', 'points')->sortable()->readOnly(),
            Number::make('pointsHardcore', 'points_hardcore')->sortable()->readOnly(),
            Number::make('pointsWeighted', 'points_weighted')->sortable()->readOnly(),
            Number::make('rankHardcore')->readOnly(),
            Number::make('rankCasual')->readOnly(),

            Number::make('yieldUnlocks', 'yield_unlocks')->sortable()->readOnly(),
            Number::make('yieldPoints', 'yield_points')->sortable()->readOnly(),

            DateTime::make('joinedAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('lastActivityAt', 'last_activity_at')->sortable()->readOnly(),
            DateTime::make('deletedAt', 'deleted_at')->readOnly(),

            Boolean::make('isUnranked')->readOnly(),
            Boolean::make('isUserWallActive', 'is_user_wall_active')->readOnly(),

            Str::make('richPresence', 'rich_presence')->readOnly(),
            DateTime::make('richPresenceUpdatedAt', 'rich_presence_updated_at')->readOnly(),

            BelongsTo::make('lastGame')->type('games')->readOnly(),

            Str::make('visibleRole')->readOnly(),
            ArrayList::make('displayableRoles')->readOnly(),

            HasMany::make('achievementSetClaims')->type('achievement-set-claims')->cannotEagerLoad()->readOnly(),
            HasMany::make('leaderboardEntries')->type('leaderboard-entries')->cannotEagerLoad()->readOnly(),
            HasMany::make('playerAchievements')->type('player-achievements')->cannotEagerLoad()->readOnly(),
            HasMany::make('playerAchievementSets')->type('player-achievement-sets')->cannotEagerLoad()->readOnly(),
            HasMany::make('playerGames')->type('player-games')->cannotEagerLoad()->readOnly(),
            HasMany::make('tickets', 'authoredTickets')
                ->type('tickets')
                ->cannotEagerLoad()
                ->withFilters(
                    new TicketStateFilter(),
                    new TicketTypeFilter(),
                    new TicketableTypeFilter(),
                    new UserUlidFilter('reporterUserId', 'reporter_id'),
                    new UserUlidFilter('resolverUserId', 'resolver_id'),
                )
                ->readOnly(),
            HasMany::make('userGameListEntries')->type('user-game-list-entries')->cannotEagerLoad()->readOnly(),
            HasMany::make('wallComments', 'visibleComments')->type('comments')->cannotEagerLoad()->readOnly(),
            HasMany::make('awards', 'playerBadges')
                ->type('user-awards')
                ->cannotEagerLoad()
                ->withFilters(
                    Scope::make('awardedFrom'),
                    Scope::make('awardedTo'),
                    Scope::make('eventId', 'forEventId'),
                    Scope::make('gameId', 'forGameId'),
                    new UserAwardGameAwardTierFilter(),
                    new UserAwardKindFilter(),
                )
                ->readOnly(),

            HasMany::make('followers', 'followedBy')
                ->type('user-follows')
                ->cannotEagerLoad()
                ->readOnly(),
            HasMany::make('following', 'follows')
                ->type('user-follows')
                ->cannotEagerLoad()
                ->readOnly(),

            // TODO add relationships and relationship endpoints
            // - authoredAchievements (HasMany Achievement)
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            Scope::make('role', 'withRole'),

            WhereNull::make('ranked', 'unranked_at'),
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
     * Excludes banned, deleted, and unverified users.
     *
     * @param Builder<User> $query
     * @return Builder<User>
     */
    public function indexQuery(?object $model, Builder $query): Builder
    {
        return $query
            ->whereNull('banned_at')
            ->verified();
    }
}
