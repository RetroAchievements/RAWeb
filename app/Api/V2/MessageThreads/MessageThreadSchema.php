<?php

declare(strict_types=1);

namespace App\Api\V2\MessageThreads;

use App\Models\MessageThread;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Str;
use App\Api\V2\MessageThreads\IsUnreadFilter;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Contracts\Pagination\Paginator as PaginatorContract;

class MessageThreadSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = MessageThread::class;

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
        return 'message-threads';
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),

            Str::make('title', 'title'),
            Str::make('body'),

            // Computed fields
            Number::make('unreadCount')->readOnly(),
            Str::make('isUnread')->readOnly(),

            // Relationships
            BelongsTo::make('recipient')->type('users'),
            HasMany::make('messages')->type('messages')->readOnly(),
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            IsUnreadFilter::make('unread'),
        ];
    }

    /**
     * Get the resource paginator.
     */
    public function pagination(): ?PaginatorContract
    {
        return PagePagination::make()
            ->withDefaultPerPage(50);
    }

    /**
     * Build an index query for this resource.
     * Only return threads where the authenticated user is a participant.
     */
    public function indexQuery(?Request $request, Builder $query): Builder
    {
        /** @var User $user */
        $user = $request?->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        return $query->forUser($user);
    }

    /**
     * Build a show query for this resource.
     * Only allow access if the user is a participant.
     *
     * @param Builder<MessageThread> $query
     * @return Builder<MessageThread>
     */
    public function showQuery(?object $model, Builder $query): Builder
    {
        /** @var User $user */
        $user = request()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        return $query->forUser($user);
    }
}
