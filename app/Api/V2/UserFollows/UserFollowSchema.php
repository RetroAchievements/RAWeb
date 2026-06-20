<?php

declare(strict_types=1);

namespace App\Api\V2\UserFollows;

use App\Models\User;
use App\Models\UserRelation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class UserFollowSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = UserRelation::class;

    /**
     * Default pagination parameters when client doesn't provide any.
     * This prevents unbounded result sets.
     */
    protected ?array $defaultPagination = ['number' => 1];

    /**
     * Default sort order shows the most recently established follows first.
     */
    protected $defaultSort = '-followedAt';

    /**
     * Eager load both sides of every relation. The presenter picks whichever
     * one is the "other" user based on perspective, so we cannot know which
     * side is needed at query time.
     */
    protected array $with = [
        'user',
        'relatedUser',
    ];

    /**
     * Get the resource type.
     */
    public static function type(): string
    {
        return 'user-follows';
    }

    /**
     * Get the resource fields.
     *
     * Most attributes (userId, displayName, etc.) project the _other_ user's
     * data and are computed by UserFollowResource via UserFollowPresenter,
     * not read from columns directly.
     *
     * isMutual is also presenter-derived from a per-request reciprocal-id set.
     */
    public function fields(): array
    {
        return [
            ID::make()->matchAs('[^/]+'),

            DateTime::make('followedAt', 'created_at')->sortable()->readOnly(),

            Str::make('userId')->readOnly(),
            Str::make('displayName')->readOnly(),
            Str::make('avatarUrl')->readOnly(),
            Number::make('points')->readOnly(),
            Number::make('pointsHardcore')->readOnly(),
            Boolean::make('isMutual')->readOnly(),
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
     * Scope the relationship query for `users/{id}/followers` and
     * `users/{id}/following` to exclude rows whose "other user" is banned.
     *
     * The "other user" lives on the opposite column from the relation's
     * foreign key: followsAsSource keys on `user_id`, so the other user is
     * `relatedUser`; followsAsTarget keys on `related_user_id`, so the other
     * user is `user`.
     *
     * @param Relation<UserRelation, User, mixed> $query
     * @return Relation<UserRelation, User, mixed>
     */
    public function relatableQuery(?Request $request, Relation $query): Relation
    {
        // Type-narrowing for getForeignKeyName(). Both followers/following routes are HasMany.
        if (!$query instanceof HasMany) {
            return $query;
        }

        $otherSideRelation = $query->getForeignKeyName() === 'user_id'
            ? 'relatedUser'
            : 'user';

        return $query->whereHas($otherSideRelation, fn (Builder $q) => $q->whereNull('banned_at'));
    }
}
