<?php

declare(strict_types=1);

namespace App\Api\V2\Comments;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class CommentSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = Comment::class;

    /**
     * Default pagination parameters when client doesn't provide any.
     * This prevents unbounded result sets.
     */
    protected ?array $defaultPagination = ['number' => 1];

    /**
     * Default sort order when client doesn't provide any.
     */
    protected $defaultSort = 'submittedAt';

    /**
     * Get the resource type.
     */
    public static function type(): string
    {
        return 'comments';
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),

            Str::make('body')->readOnly(),
            Str::make('authorAvatarUrl')->readOnly(),
            Str::make('authorDisplayName')->readOnly(),
            Str::make('authorId')->readOnly(),
            Boolean::make('isAutomated')->readOnly(),
            Str::make('permalink')->readOnly(),
            DateTime::make('submittedAt', 'created_at')->sortable()->readOnly(),

            BelongsTo::make('author', 'user')->type('users')->readOnly(),
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
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
     * @param Relation<Comment, User, mixed> $query
     * @return Relation<Comment, User, mixed>
     */
    public function relatableQuery(?Request $request, Relation $query): Relation
    {
        return $query
            ->whereHas('user')
            ->withAggregate('user as author_display_name', 'display_name')
            ->withAggregate('user as author_username', 'username')
            ->withAggregate('user as author_ulid', 'ulid');
    }
}
