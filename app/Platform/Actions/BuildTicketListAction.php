<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\TicketState;
use App\Data\PaginatedData;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Actions\Concerns\LoadsTicketListEntryRelations;
use App\Platform\Data\TicketListFilterData;
use App\Platform\Data\TicketListGroupingData;
use App\Platform\Data\TicketListStateCountsData;
use App\Platform\Enums\TicketableType;
use App\Platform\Enums\TicketListFilterKind;
use App\Platform\Enums\TicketListScope;
use App\Platform\Enums\TicketListSortField;
use App\Platform\Enums\TicketListStatusFilter;
use App\Platform\Requests\TicketListRequest;
use App\Platform\Services\TicketListService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BuildTicketListAction
{
    use LoadsTicketListEntryRelations;

    private const PER_PAGE = 50;

    /**
     * @return array{paginatedTickets: PaginatedData, stateCounts: TicketListStateCountsData, facetCounts: array<string, array<string, int>>, grouping: ?TicketListGroupingData}
     */
    public function execute(
        TicketListScope $scope,
        Game|Achievement|User|null $target,
        TicketListRequest $request,
        ?User $viewer,
    ): array {
        $service = new TicketListService();

        $filterOptions = $service->getFilterOptions($request, $scope->defaultStatusFilter());
        $comparisonUser = $scope->comparisonUser($target);

        $visibleFilterKinds = $this->getVisibleFilterKinds($scope, $viewer);
        if (!in_array(TicketListFilterKind::Signal, $visibleFilterKinds, true)) {
            $filterOptions[TicketListFilterKind::Signal->value] = TicketListFilterKind::Signal->noFilterValue();
        }

        $scopedTickets = $scope->baseQuery($target);
        $renderableTickets = (clone $scopedTickets)->withLiveTicketable();

        $stateCounts = $service->getStateCounts($filterOptions, clone $scopedTickets, $comparisonUser);

        $total = TicketListStatusFilter::from($filterOptions['status'])->filteredTotal($stateCounts);

        $facetCounts = $service->getFacetCounts(
            $filterOptions,
            clone $scopedTickets,
            $visibleFilterKinds,
            $comparisonUser,
            $total,
        );
        $lastPage = max(1, (int) ceil($total / self::PER_PAGE));
        $page = $request->getPage();

        if ($page > $lastPage) {
            $page = 1;
        }

        $query = $service->applyFilters(clone $renderableTickets, $filterOptions, $comparisonUser);
        $sort = $request->getSort();

        // If the result set is small, filter it first instead of scanning creation dates for a full page.
        // This makes several queries ~20x faster.
        if ($total <= self::PER_PAGE && $sort['field'] === TicketListSortField::CreatedAt) {
            $query->ignoreIndex('tickets_created_at_index');
        }

        $this->applyTicketListEntryEagerLoads($query);

        ['pageTickets' => $pageTickets, 'grouping' => $grouping] = match ($sort['field']) {
            TicketListSortField::Game => $this->fetchPageGroupedByGame($query, $sort['direction'], $page, $service),
            TicketListSortField::Ticketable => $this->fetchPageGroupedByTicketable($query, $sort['direction'], $page, $service),
            default => $this->fetchSortedPage($query, $sort, TicketListStatusFilter::from($filterOptions['status']), $page),
        };

        $paginator = new LengthAwarePaginator(
            items: $this->resolveTicketListEntries($pageTickets, $this->resolveTicketSignalsForViewer($pageTickets, $viewer)),
            total: $total,
            perPage: self::PER_PAGE,
            currentPage: $page,
            options: ['path' => $request->url(), 'query' => $request->query()],
        );

        $unfilteredTotal = $service->hasNonStatusFilters($filterOptions)
            ? (clone $scopedTickets)->count()
            : $stateCounts['all'];

        $paginatedTickets = PaginatedData::fromLengthAwarePaginator(
            $paginator,
            unfilteredTotal: $unfilteredTotal,
        );

        return [
            'paginatedTickets' => $paginatedTickets,
            'stateCounts' => TicketListStateCountsData::fromCounts($stateCounts),
            'facetCounts' => $facetCounts,
            'grouping' => $grouping,
        ];
    }

    /**
     * @return TicketListFilterData[]
     */
    public function getAvailableFilters(TicketListScope $scope, ?User $viewer, ?int $systemId = null): array
    {
        return array_map(
            fn (TicketListFilterKind $kind): TicketListFilterData => $this->buildFilterData($kind, $systemId),
            $this->getVisibleFilterKinds($scope, $viewer),
        );
    }

    /**
     * @return TicketListFilterKind[]
     */
    private function getVisibleFilterKinds(TicketListScope $scope, ?User $viewer): array
    {
        if ($viewer?->can('viewTriageSignals', Ticket::class)) {
            return $scope->filterKinds();
        }

        return array_values(array_filter(
            $scope->filterKinds(),
            fn (TicketListFilterKind $filterKind): bool => $filterKind !== TicketListFilterKind::Signal,
        ));
    }

    private function buildFilterData(TicketListFilterKind $kind, ?int $systemId): TicketListFilterData
    {
        $valueLabels = $kind->valueLabels();
        $values = $kind === TicketListFilterKind::System
            ? ['all', ...array_map('strval', array_keys($valueLabels))]
            : $kind->values($systemId);

        return new TicketListFilterData(
            kind: $kind,
            values: $values,
            valueLabels: (object) $valueLabels,
            isFreeText: $kind->isFreeText(),
        );
    }

    /**
     * @param Builder<Ticket> $query
     * @param array{field: TicketListSortField, direction: 'asc'|'desc'} $sort
     */
    private function applySort(Builder $query, array $sort, TicketListStatusFilter $status): void
    {
        switch ($sort['field']) {
            case TicketListSortField::State:
                $states = $status->states(); // avoid scanning unrelated statuses
                if ($states !== null) {
                    $query->whereIn('state_sort_order', array_map(fn (TicketState $state) => $state->sortOrder(), $states));
                }
                $query->orderBy('state_sort_order', $sort['direction']);
                $query->orderByDesc('created_at');
                break;

            case TicketListSortField::ResolvedAt:
                $query->orderByRaw('resolved_at IS NULL');
                $query->orderBy('resolved_at', $sort['direction']);
                $query->orderByDesc('created_at');
                break;

            case TicketListSortField::CreatedAt:
            default:
                $query->orderBy('created_at', $sort['direction']);
                break;
        }

        $query->orderByDesc('id');
    }

    /**
     * @param Builder<Ticket> $filteredQuery
     * @param array{field: TicketListSortField, direction: 'asc'|'desc'} $sort
     * @return array{pageTickets: Collection<int, Ticket>, grouping: null}
     */
    private function fetchSortedPage(Builder $filteredQuery, array $sort, TicketListStatusFilter $status, int $page): array
    {
        $this->applySort($filteredQuery, $sort, $status);

        return ['pageTickets' => $filteredQuery->forPage($page, self::PER_PAGE)->get(), 'grouping' => null];
    }

    /**
     * Sorting every ticket by its game's title is slow because the DB has to join and sort
     * the whole list just to show one page. Instead, this function counts the filtered tickets
     * per game, works out which games fall on the currently-viewed page, and fetches _only_ those
     * games' tickets. The per-game counts are also the group header counts.
     *
     * @param Builder<Ticket> $filteredQuery
     * @param 'asc'|'desc' $direction descending puts the games with the most tickets first, whereas ascending orders them A to Z
     * @return array{pageTickets: Collection<int, Ticket>, grouping: TicketListGroupingData}
     */
    private function fetchPageGroupedByGame(
        Builder $filteredQuery,
        string $direction,
        int $page,
        TicketListService $service,
    ): array {
        $ticketCountsByGame = $service->joinTicketableGameIds(clone $filteredQuery)
            ->toBase()
            ->selectRaw(TicketListService::TICKETABLE_GAME_ID . ' as group_game_id, count(*) as ticket_count')
            ->groupBy('group_game_id');

        /** @var array<int, int> $ticketCountsByGameInSortOrder */
        $ticketCountsByGameInSortOrder = DB::query()
            ->fromSub($ticketCountsByGame, 'game_ticket_counts')
            ->join('games', 'games.id', '=', 'game_ticket_counts.group_game_id')
            ->when($direction === 'desc', fn ($query) => $query->orderByDesc('game_ticket_counts.ticket_count'))
            ->orderByRaw('coalesce(games.sort_title, games.title)')
            ->orderBy('games.id')
            ->pluck('game_ticket_counts.ticket_count', 'games.id')
            ->map(fn ($ticketCount): int => (int) $ticketCount)
            ->all();

        ['ticketCountsByGame' => $pageTicketCountsByGame, 'ticketsToSkip' => $ticketsToSkip] = $this->locatePageGames(
            $ticketCountsByGameInSortOrder,
            $page,
        );

        $grouping = new TicketListGroupingData(
            field: TicketListSortField::Game,
            counts: $pageTicketCountsByGame,
            isFirstGroupContinued: $ticketsToSkip > 0,
        );
        if ($pageTicketCountsByGame === []) {
            return ['pageTickets' => new Collection(), 'grouping' => $grouping];
        }

        $gameIds = array_keys($pageTicketCountsByGame);
        $gamePositions = implode(' ', array_map(fn (int $position): string => "when ? then {$position}", array_keys($gameIds)));

        // Look the IDs up first. MariaDB scans every ticket when two IN subqueries are joined by OR,
        // but plain ID lists let the ticketable index find the page's tickets directly.
        $achievementIds = Achievement::whereIn('game_id', $gameIds)->pluck('id')->all();
        $leaderboardIds = Leaderboard::whereIn('game_id', $gameIds)->pluck('id')->all();

        $pageTickets = $service->joinTicketableGameIds($filteredQuery->select('tickets.*'))
            ->where(fn (Builder $query) => $query
                ->where(fn (Builder $achievementQuery) => $achievementQuery
                    ->where('tickets.ticketable_type', TicketableType::Achievement->value)
                    ->whereIntegerInRaw('tickets.ticketable_id', $achievementIds))
                ->orWhere(fn (Builder $leaderboardQuery) => $leaderboardQuery
                    ->where('tickets.ticketable_type', TicketableType::Leaderboard->value)
                    ->whereIntegerInRaw('tickets.ticketable_id', $leaderboardIds)))
            ->orderByRaw('case ' . TicketListService::TICKETABLE_GAME_ID . " {$gamePositions} end", $gameIds)
            ->orderByDesc('tickets.created_at')
            ->orderByDesc('tickets.id')
            ->offset($ticketsToSkip)
            ->limit(self::PER_PAGE)
            ->get();

        return ['pageTickets' => $pageTickets, 'grouping' => $grouping];
    }

    /**
     * Walks the games in sort order until the page is full.
     *
     * @param array<int, int> $ticketCountsByGameInSortOrder
     * @return array{ticketCountsByGame: array<int, int>, ticketsToSkip: int}
     */
    private function locatePageGames(array $ticketCountsByGameInSortOrder, int $page): array
    {
        $pageStart = ($page - 1) * self::PER_PAGE;
        $pageEnd = $pageStart + self::PER_PAGE;
        $ticketsBeforeGame = 0;
        $ticketsToSkip = 0;
        $pageTicketCountsByGame = [];

        foreach ($ticketCountsByGameInSortOrder as $gameId => $ticketCount) {
            if ($ticketsBeforeGame >= $pageEnd) {
                break;
            }

            if ($ticketsBeforeGame + $ticketCount > $pageStart) {
                if ($pageTicketCountsByGame === []) {
                    $ticketsToSkip = $pageStart - $ticketsBeforeGame;
                }
                $pageTicketCountsByGame[$gameId] = $ticketCount;
            }
            $ticketsBeforeGame += $ticketCount;
        }

        return ['ticketCountsByGame' => $pageTicketCountsByGame, 'ticketsToSkip' => $ticketsToSkip];
    }

    /**
     * @param Builder<Ticket> $filteredQuery
     * @param 'asc'|'desc' $direction
     * @return array{pageTickets: Collection<int, Ticket>, grouping: TicketListGroupingData}
     */
    private function fetchPageGroupedByTicketable(
        Builder $filteredQuery,
        string $direction,
        int $page,
        TicketListService $service,
    ): array {
        $ticketCountsByTicketable = (clone $filteredQuery)
            ->toBase()
            ->select(['tickets.ticketable_type as group_ticketable_type', 'tickets.ticketable_id as group_ticketable_id'])
            ->selectRaw('count(*) as group_ticket_count')
            ->groupBy('tickets.ticketable_type', 'tickets.ticketable_id');

        $hasPreviousPage = $page > 1;
        $previousPageRowCount = $hasPreviousPage ? 1 : 0;
        $pageTickets = $service->joinTicketables(
            $filteredQuery->select(['tickets.*', 'group_ticket_count']),
            ['title as facet_achievement_title'],
            ['title as facet_leaderboard_title'],
        )
            ->joinSub($ticketCountsByTicketable, 'ticketable_ticket_counts', fn ($join) => $join
                ->on('group_ticketable_type', '=', 'tickets.ticketable_type')
                ->on('group_ticketable_id', '=', 'tickets.ticketable_id'))
            ->when($direction === 'desc', fn (Builder $query) => $query->orderByDesc('group_ticket_count'))
            ->orderByRaw('coalesce(facet_achievement_title, facet_leaderboard_title)')
            ->orderBy('tickets.ticketable_type')
            ->orderBy('tickets.ticketable_id')
            ->orderByDesc('tickets.created_at')
            ->orderByDesc('tickets.id')
            ->offset(($page - 1) * self::PER_PAGE - $previousPageRowCount)
            ->limit(self::PER_PAGE + $previousPageRowCount)
            ->get();

        $previousPageLastTicket = $hasPreviousPage ? $pageTickets->shift() : null;
        $firstTicket = $pageTickets->first();

        return [
            'pageTickets' => $pageTickets,
            'grouping' => new TicketListGroupingData(
                field: TicketListSortField::Ticketable,
                counts: $pageTickets
                    ->mapWithKeys(fn (Ticket $ticket): array => [
                        "{$ticket->ticketable_type}:{$ticket->ticketable_id}" => (int) $ticket->getAttribute('group_ticket_count'),
                    ])
                    ->all(),
                isFirstGroupContinued:
                    $previousPageLastTicket !== null
                    && $firstTicket !== null
                    && $previousPageLastTicket->ticketable_type === $firstTicket->ticketable_type
                    && $previousPageLastTicket->ticketable_id === $firstTicket->ticketable_id,
            ),
        ];
    }
}
