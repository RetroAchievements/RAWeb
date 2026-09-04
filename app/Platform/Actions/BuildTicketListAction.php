<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\TicketState;
use App\Data\PaginatedData;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Actions\Concerns\LoadsTicketListEntryRelations;
use App\Platform\Data\TicketListFilterData;
use App\Platform\Data\TicketListStateCountsData;
use App\Platform\Enums\TicketListFilterKind;
use App\Platform\Enums\TicketListScope;
use App\Platform\Enums\TicketListSortField;
use App\Platform\Enums\TicketListStatusFilter;
use App\Platform\Requests\TicketListRequest;
use App\Platform\Services\TicketListService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class BuildTicketListAction
{
    use LoadsTicketListEntryRelations;

    private const PER_PAGE = 50;

    /**
     * @return array{paginatedTickets: PaginatedData, stateCounts: TicketListStateCountsData, facetCounts: array<string, array<string, int>>}
     */
    public function execute(
        TicketListScope $scope,
        Game|Achievement|User|null $target,
        TicketListRequest $request,
    ): array {
        $service = new TicketListService();

        $filterOptions = $service->getFilterOptions($request, $scope->defaultStatusFilter());
        $comparisonUser = $scope->comparisonUser($target);

        $scopedTickets = $scope->baseQuery($target);
        $renderableTickets = (clone $scopedTickets)->withLiveTicketable();

        $stateCounts = $service->getStateCounts($filterOptions, clone $scopedTickets, $comparisonUser);

        $total = TicketListStatusFilter::from($filterOptions['status'])->filteredTotal($stateCounts);

        $facetCounts = $service->getFacetCounts(
            $filterOptions,
            clone $scopedTickets,
            $scope->filterKinds(),
            $comparisonUser,
            $total,
        );
        $lastPage = max(1, (int) ceil($total / self::PER_PAGE));
        $page = $request->getPage();

        if ($page > $lastPage) {
            $page = 1;
        }

        $query = $service->applyFilters(clone $renderableTickets, $filterOptions, $comparisonUser);
        $this->applySort($query, $request->getSort());
        $this->applyTicketListEntryEagerLoads($query);

        $paginator = new LengthAwarePaginator(
            items: $this->fetchTicketListEntries($query->forPage($page, self::PER_PAGE)),
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
        ];
    }

    /**
     * @return TicketListFilterData[]
     */
    public function getAvailableFilters(TicketListScope $scope, ?int $systemId = null): array
    {
        return array_map(
            fn (TicketListFilterKind $kind) => new TicketListFilterData(
                kind: $kind,
                values: $kind->values($systemId),
            ),
            $scope->filterKinds(),
        );
    }

    /**
     * @param Builder<Ticket> $query
     * @param array{field: TicketListSortField, direction: 'asc'|'desc'} $sort
     */
    private function applySort(Builder $query, array $sort): void
    {
        switch ($sort['field']) {
            case TicketListSortField::State:
                $stateOrder = [
                    TicketState::Open,
                    TicketState::Request,
                    TicketState::Quarantined,
                    TicketState::Resolved,
                    TicketState::Closed,
                ];
                $cases = implode(' ', array_map(
                    fn (int $index) => "WHEN ? THEN {$index}",
                    array_keys($stateOrder),
                ));
                $query->orderByRaw(
                    "CASE state {$cases} ELSE ? END {$sort['direction']}",
                    [...array_map(fn (TicketState $state) => $state->value, $stateOrder), count($stateOrder)],
                );
                $query->orderByDesc('created_at');
                break;

            case TicketListSortField::ResolvedAt:
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
}
