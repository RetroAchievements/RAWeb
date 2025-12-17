<?php

declare(strict_types=1);

namespace App\Api\V2\Users;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\ArrayList;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
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
     * Default sort order when client doesn't provide any.
     * Shows highest point users first for leaderboard-style results.
     */
    protected $defaultSort = '-points';

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
            ID::make('ulid')->matchAs('.+'),

            Str::make('displayName', 'display_name'),
            Str::make('username', 'User'),

            Str::make('avatarUrl')->readOnly(),
            Str::make('motto', 'Motto'),

            Number::make('points', 'RAPoints')->sortable(),
            Number::make('pointsSoftcore', 'RASoftcorePoints'),
            Number::make('pointsWeighted', 'TrueRAPoints')->sortable(),

            Number::make('yieldUnlocks', 'ContribCount'),
            Number::make('yieldPoints', 'ContribYield'),

            DateTime::make('joinedAt', 'Created')->sortable(),
            DateTime::make('lastActivityAt', 'LastLogin')->sortable(),

            Boolean::make('isUnranked')->readOnly(),
            Boolean::make('isUserWallActive', 'UserWallActive'),

            Str::make('richPresenceMessage', 'RichPresenceMsg'),
            DateTime::make('richPresenceUpdatedAt', 'RichPresenceMsgDate'),

            Str::make('visibleRole')->readOnly(),
            ArrayList::make('displayableRoles')->readOnly(),

            // TODO add relationships
            // - lastGame (BelongsTo Game)
            // - playerGames (HasMany PlayerGame)
            // - playerAchievementSets (HasMany PlayerAchievementSet)
            // - playerAchievements (HasMany PlayerAchievement)
            // - awards (HasMany PlayerBadge)
            // - following (BelongsToMany User) - users this user follows
            // - followers (BelongsToMany User) - users following this user
            // - authoredAchievements (HasMany Achievement)
            // - claims (HasMany AchievementSetClaim)
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            Where::make('displayName', 'display_name'),
            Where::make('username', 'User'),
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
