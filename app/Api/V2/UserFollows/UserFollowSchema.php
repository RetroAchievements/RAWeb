<?php

declare(strict_types=1);

namespace App\Api\V2\UserFollows;

use App\Community\Enums\UserRelationStatus;
use App\Models\User;
use App\Models\UserRelation;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class UserFollowSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = UserRelation::class;

    protected int $maxDepth = 2;

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
     * The displayed user can be on either side of the relation.
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
     */
    public function fields(): array
    {
        return [
            ID::make()->matchAs('[^/]+'),

            DateTime::make('followedAt', 'created_at')->sortable()->readOnly(),

            Str::make('userId')->readOnly(),
            Str::make('displayName', 'displayed_users.display_name')->sortable()->readOnly(),
            Str::make('avatarUrl')->readOnly(),
            Number::make('points', 'displayed_users.points')->sortable()->readOnly(),
            Number::make('pointsHardcore', 'displayed_users.points_hardcore')->sortable()->readOnly(),
            Boolean::make('isMutual')->readOnly(),

            BelongsTo::make('user', 'relatedUser')->type('users')->readOnly(),
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
     * Exclude rows where the displayed user is banned.
     *
     * @param Relation<UserRelation, User, mixed> $query
     * @return Relation<UserRelation, User, mixed>
     */
    public function relatableQuery(?Request $request, Relation $query): Relation
    {
        if (!$query instanceof HasMany) {
            return $query;
        }

        $displayedUserForeignKey = $query->getForeignKeyName() === 'user_id'
            ? 'related_user_id'
            : 'user_id';

        return $query
            ->where('user_relations.status', '=', UserRelationStatus::Following)
            ->leftJoin('users as displayed_users', 'displayed_users.id', '=', 'user_relations.' . $displayedUserForeignKey)
            ->whereNull('displayed_users.banned_at')
            ->select('user_relations.*');
    }
}
