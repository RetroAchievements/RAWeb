<?php

declare(strict_types=1);

namespace App\Api\V2\Tickets;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class TicketSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = Ticket::class;

    protected int $maxDepth = 2;

    /**
     * Default pagination parameters when client doesn't provide any.
     * This prevents unbounded result sets.
     */
    protected ?array $defaultPagination = ['number' => 1];

    /**
     * Default sort order.
     */
    protected $defaultSort = '-reportedAt';

    /**
     * Relationships that should always be eager loaded.
     */
    protected array $with = [
        'achievement.game.system',
        'leaderboard.game.system',
        'reporter',
        'resolver',
        'author',
    ];

    public static function type(): string
    {
        return 'tickets';
    }

    /**
     * Custom repository so the show route applies `scopeVisibleTo` at lookup.
     */
    public function repository(): TicketRepository
    {
        return new TicketRepository(
            $this,
            $this->driver(),
            $this->parser(),
        );
    }

    public function id(): ID
    {
        return ID::make()->matchAs('[^/]+')->sortable();
    }

    public function fields(): array
    {
        return [
            $this->id(),

            Str::make('state')->sortable()->readOnly(),
            Str::make('type')->readOnly(),
            Str::make('body')->readOnly(),
            Boolean::make('hardcore')->readOnly(),

            DateTime::make('reportedAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('resolvedAt', 'resolved_at')->sortable()->readOnly(),

            Str::make('ticketableType', 'ticketable_type')->readOnly(),
            Number::make('ticketableId', 'ticketable_id')->readOnly(),
            Str::make('gameIconUrl')->readOnly(),
            Str::make('systemName')->readOnly(),

            BelongsTo::make('achievement')->type('achievements')->readOnly(),
            BelongsTo::make('leaderboard')->type('leaderboards')->readOnly(),
            BelongsTo::make('reporter')->type('users')->readOnly(),
            BelongsTo::make('resolver')->type('users')->readOnly(),
            BelongsTo::make('author')->type('users')->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this)->delimiter(','),

            new TicketStateFilter(),
            new TicketTypeFilter(),
            new TicketableTypeFilter(),
            new UserUlidFilter('reporterUserId', 'reporter_id'),
            new UserUlidFilter('resolverUserId', 'resolver_id'),
            new AchievementIdFilter(),
            new LeaderboardIdFilter(),
            new GameIdFilter(),
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make()
            ->withDefaultPerPage(25);
    }

    /**
     * @param Builder<Ticket> $query
     * @return Builder<Ticket>
     */
    public function indexQuery(?object $request, Builder $query): Builder
    {
        $caller = $request instanceof Request ? $request->user() : null;

        $query->visibleTo($caller);

        if (!$this->hasStateFilter($request)) {
            $query->open();
        }

        return $query;
    }

    /**
     * @param Relation<Ticket, Achievement|Game|User, mixed> $query
     * @return Relation<Ticket, Achievement|Game|User, mixed>
     */
    public function relatableQuery(?Request $request, Relation $query): Relation
    {
        $caller = $request?->user();
        $parent = $query->getParent();

        if (!($parent instanceof Achievement || $parent instanceof Game || $parent instanceof User)) {
            $query->whereRaw('1 = 0');

            return $query;
        }

        $query->visibleTo($caller);

        if (!$this->hasStateFilter($request)) {
            $query->open();
        }

        return $query;
    }

    /**
     * When no `filter[state]` is supplied, the index returns open tickets only.
     */
    private function hasStateFilter(?object $request): bool
    {
        return $request instanceof Request
            && filled(data_get($request->query(), 'filter.state'));
    }
}
