<?php

declare(strict_types=1);

namespace App\Api\V2\LookingForGroupPosts;

use App\Models\LookingForGroupPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class LookingForGroupPostSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = LookingForGroupPost::class;

    /**
     * Default pagination parameters when client doesn't provide any.
     * This prevents unbounded result sets.
     */
    protected ?array $defaultPagination = ['number' => 1];

    /**
     * Default sort order when client doesn't provide any.
     * Shows most recently created posts first.
     */
    protected $defaultSort = '-createdAt';

    /**
     * Get the resource type.
     */
    public static function type(): string
    {
        return 'looking-for-group-posts';
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),

            Str::make('title', 'title'),
            Str::make('note', 'note'),

            Number::make('maxPlayers', 'max_players'),
            Number::make('acceptedPlayersCount')->readOnly(),
            Number::make('availableSlotsCount')->readOnly(),

            Str::make('status', 'status'),

            DateTime::make('scheduledFor', 'scheduled_for'),
            DateTime::make('expiresAt', 'expires_at')->sortable(),
            DateTime::make('createdAt', 'created_at')->sortable(),

            // Relationships
            BelongsTo::make('game')->type('games'),
            BelongsTo::make('creator')->type('users')->readOnly(),
            HasMany::make('invites')->type('looking-for-group-invites')->readOnly(),
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('status', 'status'),
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
     * Only return active posts for verified users.
     *
     * @param Builder<LookingForGroupPost> $query
     * @return Builder<LookingForGroupPost>
     */
    public function indexQuery(?Request $request, Builder $query): Builder
    {
        /** @var User $user */
        $user = $request?->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        return $query->active();
    }

    /**
     * Build a show query for this resource.
     * Only allow access for verified users.
     *
     * @param Builder<LookingForGroupPost> $query
     * @return Builder<LookingForGroupPost>
     */
    public function showQuery(?Request $request, Builder $query): Builder
    {
        /** @var User $user */
        $user = $request?->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        return $query;
    }
}
