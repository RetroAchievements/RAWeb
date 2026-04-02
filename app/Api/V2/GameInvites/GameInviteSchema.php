<?php

declare(strict_types=1);

namespace App\Api\V2\GameInvites;

use App\Models\GameInvite;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class GameInviteSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = GameInvite::class;

    /**
     * Default pagination parameters when client doesn't provide any.
     * This prevents unbounded result sets.
     */
    protected ?array $defaultPagination = ['number' => 1];

    /**
     * Default sort order when client doesn't provide any.
     * Shows most recently sent invites first.
     */
    protected $defaultSort = '-sentAt';

    /**
     * Get the resource type.
     */
    public static function type(): string
    {
        return 'game-invites';
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),

            Str::make('message', 'message'),

            Str::make('status', 'status'),

            DateTime::make('sentAt', 'sent_at')->sortable(),
            DateTime::make('respondedAt', 'responded_at')->readOnly(),
            DateTime::make('expiresAt', 'expires_at')->sortable(),

            // Relationships
            BelongsTo::make('game')->type('games')->readOnly(),
            BelongsTo::make('sender')->type('users')->readOnly(),
            BelongsTo::make('recipient')->type('users')->readOnly(),
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
            RoleFilter::make('role'),
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
     * Only return invites where the authenticated user is sender or recipient.
     *
     * @param Builder<GameInvite> $query
     * @return Builder<GameInvite>
     */
    public function indexQuery(?Request $request, Builder $query): Builder
    {
        /** @var User $user */
        $user = $request?->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // Default: user's own invites (both sent and received)
        return $query->where(function ($q) use ($user) {
            $q->where('sender_user_id', $user->id)
              ->orWhere('recipient_user_id', $user->id);
        });
    }

    /**
     * Build a show query for this resource.
     * Only allow access if the user is sender or recipient.
     *
     * @param Builder<GameInvite> $query
     * @return Builder<GameInvite>
     */
    public function showQuery(?Request $request, Builder $query): Builder
    {
        /** @var User $user */
        $user = $request?->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($q) use ($user) {
            $q->where('sender_user_id', $user->id)
              ->orWhere('recipient_user_id', $user->id);
        });
    }
}
