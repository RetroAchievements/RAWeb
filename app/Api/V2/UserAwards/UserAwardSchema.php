<?php

declare(strict_types=1);

namespace App\Api\V2\UserAwards;

use App\Models\PlayerBadge;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Scope;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class UserAwardSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = PlayerBadge::class;

    protected int $maxDepth = 2;

    /**
     * Default pagination parameters when client doesn't provide any.
     * This prevents unbounded result sets.
     */
    protected ?array $defaultPagination = ['number' => 1];

    /**
     * Default sort order when client doesn't provide any.
     *
     * The top-level index is a site-wide recency feed over millions of rows,
     * so it defaults to -awardedAt, which the awarded_at index can satisfy.
     * The /users/{user}/awards relationship endpoint intentionally mirrors
     * the profile award ordering instead.
     */
    public function defaultSort(): mixed
    {
        if ($this->isServingIndexRoute()) {
            return '-awardedAt';
        }

        return ['displayOrder', 'awardedAt'];
    }

    /**
     * Relationships that should always be eager loaded.
     */
    protected array $with = [
        'eventIfApplicable',
        'eventIfApplicable.awards',
        'eventIfApplicable.legacyGame',
        'gameIfApplicable',
        'gameIfApplicable.system',
        'siteAwardIfApplicable',
        'user',
    ];

    /**
     * Get the resource type.
     */
    public static function type(): string
    {
        return 'user-awards';
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),

            DateTime::make('awardedAt', 'awarded_at')->sortable()->readOnly(),
            Str::make('kind', 'award_type')->readOnly(),
            Str::make('title', 'award_key')->readOnly(),
            Str::make('badgeUrl', 'award_key')->readOnly(),
            Number::make('displayOrder', 'order_column')->sortable()->readOnly(),
            ArrayHash::make('context', 'award_key')->readOnly(),
            Str::make('userDisplayName')->readOnly(),
            Str::make('userId')->readOnly(),

            BelongsTo::make('event', 'eventIfApplicable')->type('events')->readOnly(),
            BelongsTo::make('game', 'gameIfApplicable')->type('games')->readOnly(),
            BelongsTo::make('user')->type('users')->readOnly(),
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this)->delimiter(','),
            Scope::make('awardedFrom'),
            Scope::make('awardedTo'),
            Scope::make('eventId', 'forEventId'),
            Scope::make('gameId', 'forGameId'),
            new UserAwardGameAwardTierFilter(),
            new UserAwardKindFilter(),
        ];
    }

    /**
     * Get the resource paginator.
     */
    public function pagination(): ?Paginator
    {
        if ($this->isServingIndexRoute()) {
            return UserAwardIndexPagination::make()
                ->withDefaultPerPage(50)
                ->withSimplePagination();
        }

        return PagePagination::make()
            ->withDefaultPerPage(50);
    }

    /**
     * Banned users' awards are hidden site-wide.
     *
     * @param Builder<PlayerBadge> $query
     * @return Builder<PlayerBadge>
     */
    public function indexQuery(?Request $request, Builder $query): Builder
    {
        return $query->whereHas('user', fn (Builder $q) => $q->whereNull('banned_at'));
    }

    /**
     * The schema serves both the top-level index and the users/{user}/awards
     * relationship endpoint, and the two intentionally differ in default sort
     * and pagination strategy.
     */
    private function isServingIndexRoute(): bool
    {
        return request()->routeIs('v2.user-awards.index');
    }

    /**
     * @param Relation<PlayerBadge, User, mixed> $query
     * @return Relation<PlayerBadge, User, mixed>
     */
    public function relatableQuery(?Request $request, Relation $query): Relation
    {
        $parent = $query->getParent();

        if ($parent instanceof User) {
            return $query
                ->canonicalForApiUser($parent->id)
                ->visibleOnUserProfile();
        }

        return $query;
    }
}
