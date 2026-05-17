<?php

declare(strict_types=1);

namespace App\Api\V2\AchievementSetClaims;

use App\Api\V2\AchievementSetClaims\Filters\ClaimExpiredFilter;
use App\Api\V2\AchievementSetClaims\Filters\ClaimSetTypeFilter;
use App\Api\V2\AchievementSetClaims\Filters\ClaimSpecialTypeFilter;
use App\Api\V2\AchievementSetClaims\Filters\ClaimStatusFilter;
use App\Api\V2\AchievementSetClaims\Filters\ClaimTypeFilter;
use App\Api\V2\AchievementSetClaims\Filters\ClaimUserFilter;
use App\Api\V2\AchievementSetClaims\Sorts\SortByGameTitle;
use App\Api\V2\AchievementSetClaims\Sorts\SortByUserDisplayName;
use App\Models\AchievementSetClaim;
use App\Models\Game;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
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

class AchievementSetClaimSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = AchievementSetClaim::class;

    /**
     * Default pagination parameters when client doesn't provide any.
     * This prevents unbounded result sets.
     */
    protected ?array $defaultPagination = ['number' => 1];

    /**
     * Default sort order when client doesn't provide any.
     */
    protected $defaultSort = '-claimedAt';

    /**
     * Relationships that should always be eager loaded.
     */
    protected array $with = [
        'user',
        'game',
        'game.system',
    ];

    /**
     * Get the resource type.
     */
    public static function type(): string
    {
        return 'achievement-set-claims';
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),

            DateTime::make('claimedAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('finishedAt', 'finished_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),

            Str::make('status')->sortable()->readOnly(),
            Str::make('claimType', 'claim_type')->sortable()->readOnly(),
            Str::make('setType', 'set_type')->sortable()->readOnly(),
            Str::make('specialType', 'special_type')->sortable()->readOnly(),

            Number::make('extensionsCount', 'extensions_count')->sortable()->readOnly(),
            Number::make('minutesLeft')->readOnly(),

            Str::make('userId')->readOnly(),
            Str::make('userDisplayName')->readOnly(),
            Number::make('gameId', 'game_id')->readOnly(),
            Str::make('gameTitle')->readOnly(),
            Str::make('gameIconUrl')->readOnly(),
            Number::make('systemId')->readOnly(),
            Str::make('systemName')->readOnly(),

            BelongsTo::make('user')->type('users')->readOnly(),
            BelongsTo::make('game')->type('games')->readOnly(),
        ];
    }

    /**
     * Get the sortable fields for this resource.
     */
    public function sortables(): iterable
    {
        return [
            new SortByGameTitle(),
            new SortByUserDisplayName(),
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this)->delimiter(','),
            Where::make('gameId', 'game_id'),

            new ClaimStatusFilter(),
            new ClaimTypeFilter(),
            new ClaimSetTypeFilter(),
            new ClaimSpecialTypeFilter(),
            new ClaimExpiredFilter(),
            new ClaimUserFilter(),
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
     * Build the index query for the top-level `/achievement-set-claims` route.
     * Excludes claims belonging to banned users.
     *
     * @param Builder<AchievementSetClaim> $query
     * @return Builder<AchievementSetClaim>
     */
    public function indexQuery(?object $request, Builder $query): Builder
    {
        return $query->whereHas('user', fn (Builder $q) => $q->whereNull('banned_at'));
    }

    /**
     * Scopes the query when claims are accessed through a parent relationship
     * route (eg: `/users/{user}/achievement-set-claims`). The `users` parent
     * route is already gated by `UserPolicy::view`, which throws a banned user
     * exception before this runs, so banned user filtering is only required
     * on the `games` parent path.
     *
     * @param Relation<AchievementSetClaim, User|Game, mixed> $query
     * @return Relation<AchievementSetClaim, User|Game, mixed>
     */
    public function relatableQuery(?Request $request, Relation $query): Relation
    {
        $parent = $query->getParent();

        if ($parent instanceof User) {
            return $query->where('user_id', $parent->id);
        }

        if ($parent instanceof Game) {
            return $query
                ->where('game_id', $parent->id)
                ->whereHas('user', fn (Builder $q) => $q->whereNull('banned_at'));
        }

        return $query;
    }
}
