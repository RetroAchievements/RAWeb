<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Community\Enums\TicketState;
use App\Community\Enums\TicketType;
use App\Enums\Permissions;
use App\Models\Achievement;
use App\Models\Emulator;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Enums\TicketableType;
use App\Platform\Enums\TicketListFilterKind;
use App\Platform\Enums\TicketListStatusFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TicketListService
{
    private const MAX_FACET_COUNT_ROWS = 10_000;

    /** @var array<int, string>|null */
    private ?array $emulatorNamesById = null;

    /** @var array<string, int> */
    private array $countsForCurrentBaseQuery = [];

    /**
     * @return array{status: string, type: int, publishedStatus: string, mode: string, developerType: string, developer: string, reporter: string, emulator: string}
     */
    public function getFilterOptions(Request $request, TicketListStatusFilter $defaultStatus = TicketListStatusFilter::Unresolved): array
    {
        $rules = ['filter.status' => ['sometimes', Rule::enum(TicketListStatusFilter::class)]];
        foreach (TicketListFilterKind::cases() as $kind) {
            $rules["filter.{$kind->value}"] = $kind->validationRules();
        }

        $validatedData = $request->validate($rules);

        return [
            'status' => $validatedData['filter']['status'] ?? $defaultStatus->value,
            'type' => (int) ($validatedData['filter']['type'] ?? 0),
            'publishedStatus' => $validatedData['filter']['publishedStatus'] ?? 'all',
            'mode' => $validatedData['filter']['mode'] ?? 'all',
            'developerType' => $validatedData['filter']['developerType'] ?? 'all',
            'developer' => $validatedData['filter']['developer'] ?? 'all',
            'reporter' => $validatedData['filter']['reporter'] ?? 'all',
            'emulator' => $validatedData['filter']['emulator'] ?? 'all',
        ];
    }

    public function hasNonStatusFilters(array $filterOptions): bool
    {
        foreach (TicketListFilterKind::cases() as $kind) {
            $noFilterValue = $kind->noFilterValue();

            if (($filterOptions[$kind->value] ?? $noFilterValue) !== $noFilterValue) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Builder<Ticket> $tickets
     * @return Builder<Ticket>
     */
    public function applyFilters(Builder $tickets, array $filterOptions, ?User $comparisonUser = null): Builder
    {
        switch (TicketListStatusFilter::from($filterOptions['status'])) {
            case TicketListStatusFilter::Unresolved:
                $tickets->open();
                break;

            case TicketListStatusFilter::Open:
                $tickets->where('state', TicketState::Open);
                break;

            case TicketListStatusFilter::Request:
                $tickets->where('state', TicketState::Request);
                break;

            case TicketListStatusFilter::Resolved:
                $tickets->where('state', TicketState::Resolved);
                break;

            case TicketListStatusFilter::Closed:
                $tickets->where('state', TicketState::Closed);
                break;

            case TicketListStatusFilter::Quarantined:
                $tickets->quarantined();
                break;

            case TicketListStatusFilter::All:
                break;
        }

        if ($filterOptions['type'] > 0) {
            $ticketType = TicketType::fromLegacyInteger($filterOptions['type']);
            $tickets->where('type', $ticketType);
        }

        switch ($filterOptions['publishedStatus']) {
            case 'published':
                $tickets->promoted();
                break;

            case 'unpublished':
                $tickets->unpromoted();
                break;
        }

        switch ($filterOptions['mode']) {
            case 'hardcore':
                $tickets->where('hardcore', true);
                break;

            case 'softcore':
                $tickets->where('hardcore', false);
                break;

            case 'unspecified':
                $tickets->whereNull('hardcore');
                break;
        }

        switch ($filterOptions['developerType']) {
            case 'active':
                $tickets->whereHas('author', function ($query) {
                    $query->where('Permissions', '>=', Permissions::JuniorDeveloper);
                });
                break;

            case 'junior':
                $tickets->whereHas('author', function ($query) {
                    $query->where('Permissions', '=', Permissions::JuniorDeveloper);
                });
                break;

            case 'inactive':
                // For achievement tickets, also exclude any with an active maintainer.
                // Leaderboards don't have a maintainer concept, so author permissions
                // alone are checked.
                $tickets->where(function ($query) {
                    $query->where(function ($achievementQuery) {
                        $achievementQuery
                            ->where('ticketable_type', TicketableType::Achievement->value)
                            ->whereHasMorph('ticketable', [Achievement::class], function ($ticketableQuery) {
                                $ticketableQuery->whereDoesntHave('activeMaintainer');
                            });
                    })->orWhere('ticketable_type', TicketableType::Leaderboard->value);
                })->whereHas('author', function ($query) {
                    $query->where('Permissions', '<', Permissions::JuniorDeveloper);
                });
                break;
        }

        if ($comparisonUser !== null) {
            switch ($filterOptions['developer']) {
                case 'all':
                    break;

                case 'self':
                    $tickets->where('ticketable_author_id', '=', $comparisonUser->id);
                    break;

                case 'others':
                    $tickets->where('ticketable_author_id', '!=', $comparisonUser->id);
                    break;
            }

            switch ($filterOptions['reporter']) {
                case 'all':
                    break;

                case 'self':
                    $tickets->where('reporter_id', '=', $comparisonUser->id);
                    break;

                case 'others':
                    $tickets->where('reporter_id', '!=', $comparisonUser->id);
                    break;
            }
        }

        if ($filterOptions['emulator']) {
            if ($filterOptions['emulator'] === 'unknown') {
                $tickets->whereNull('emulator_id');
            } elseif ($filterOptions['emulator'] !== 'all') {
                $emulator = Emulator::where('name', $filterOptions['emulator'])->first();
                if ($emulator) {
                    $tickets->where('emulator_id', '=', $emulator->id);
                }
            }
        }

        return $tickets;
    }

    /**
     * How many tickets each filter option matches.
     *
     * @param Builder<Ticket> $tickets
     * @param list<TicketListFilterKind> $kinds
     * @return array<string, array<string, int>>
     */
    public function getFacetCounts(
        array $filterOptions,
        Builder $tickets,
        array $kinds,
        ?User $comparisonUser = null,
        ?int $filteredTotal = null,
    ): array {
        $this->countsForCurrentBaseQuery = [];

        $widestFacetRowCount = $this->hasNonStatusFilters($filterOptions)
            ? $this->countForFilters($tickets, $this->withoutFacetFilters($filterOptions), $comparisonUser)
            : $filteredTotal;

        if ($widestFacetRowCount !== null && $widestFacetRowCount > self::MAX_FACET_COUNT_ROWS) {
            return [];
        }

        $counts = [];
        foreach ($kinds as $kind) {
            $facetCounts = match ($kind) {
                TicketListFilterKind::Type => $this->countGroupedFacet(
                    $tickets,
                    $filterOptions,
                    $comparisonUser,
                    $kind,
                    'tickets.type',
                    fn (?string $value) => match ($value) {
                        TicketType::TriggeredAtWrongTime->value => (string) TicketType::TriggeredAtWrongTime->toLegacyInteger(),
                        TicketType::DidNotTrigger->value => (string) TicketType::DidNotTrigger->toLegacyInteger(),
                        default => null,
                    },
                ),
                TicketListFilterKind::Mode => $this->countGroupedFacet(
                    $tickets,
                    $filterOptions,
                    $comparisonUser,
                    $kind,
                    'tickets.hardcore',
                    fn (?string $value) => match ($value) {
                        null => 'unspecified',
                        '1' => 'hardcore',
                        default => 'softcore',
                    },
                ),
                TicketListFilterKind::Emulator => $this->countGroupedFacet(
                    $tickets,
                    $filterOptions,
                    $comparisonUser,
                    $kind,
                    'tickets.emulator_id',
                    fn (?string $value) => $value === null
                        ? 'unknown'
                        : ($this->emulatorNamesById()[(int) $value] ?? null),
                ),
                TicketListFilterKind::PublishedStatus,
                TicketListFilterKind::DeveloperType => $this->countFacetOptions(
                    $tickets,
                    $filterOptions,
                    $comparisonUser,
                    $kind,
                    $filteredTotal,
                ),
                TicketListFilterKind::Developer,
                TicketListFilterKind::Reporter => null,
            };

            if ($facetCounts !== null) {
                $counts[$kind->value] = $facetCounts;
            }
        }

        return $counts;
    }

    /**
     * @param Builder<Ticket> $tickets
     * @param callable(?string): ?string $toFilterValue
     * @return array<string, int>
     */
    private function countGroupedFacet(
        Builder $tickets,
        array $filterOptions,
        ?User $comparisonUser,
        TicketListFilterKind $kind,
        string $column,
        callable $toFilterValue,
    ): array {
        $rows = $this->applyFilters(clone $tickets, $this->withoutFilter($filterOptions, $kind), $comparisonUser)
            ->reorder()
            ->select($column . ' as facet_value', DB::raw('count(*) as aggregate'))
            ->groupBy('facet_value')
            ->get();

        $noFilterValue = (string) $kind->noFilterValue();
        $counts = [$noFilterValue => 0];

        foreach ($rows as $row) {
            $aggregate = (int) $row->aggregate;
            $counts[$noFilterValue] += $aggregate;

            $value = $toFilterValue($row->facet_value === null ? null : (string) $row->facet_value);
            if ($value !== null) {
                $counts[$value] = ($counts[$value] ?? 0) + $aggregate;
            }
        }

        return $counts;
    }

    /**
     * @param Builder<Ticket> $tickets
     * @return array<string, int>
     */
    private function countFacetOptions(
        Builder $tickets,
        array $filterOptions,
        ?User $comparisonUser,
        TicketListFilterKind $kind,
        ?int $filteredTotal,
    ): array {
        $noFilterValue = $kind->noFilterValue();
        $lifted = $this->withoutFilter($filterOptions, $kind);

        $isAlreadyUnfiltered = ($filterOptions[$kind->value] ?? $noFilterValue) === $noFilterValue;

        $counts = [];
        foreach ($kind->values() as $value) {
            $counts[$value] = $value === (string) $noFilterValue && $isAlreadyUnfiltered && $filteredTotal !== null
                ? $filteredTotal
                : $this->countForFilters(
                    $tickets,
                    array_merge($lifted, [$kind->value => $value]),
                    $comparisonUser,
                );
        }

        return $counts;
    }

    /**
     * @param Builder<Ticket> $tickets
     */
    private function countForFilters(Builder $tickets, array $filterOptions, ?User $comparisonUser): int
    {
        $key = serialize([$filterOptions, $comparisonUser?->id]);

        return $this->countsForCurrentBaseQuery[$key] ??= $this->applyFilters(
            clone $tickets,
            $filterOptions,
            $comparisonUser,
        )->count();
    }

    /**
     * @return array<int, string>
     */
    private function emulatorNamesById(): array
    {
        return $this->emulatorNamesById ??= Emulator::pluck('name', 'id')->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function withoutFilter(array $filterOptions, TicketListFilterKind $kind): array
    {
        return array_merge($filterOptions, [$kind->value => $kind->noFilterValue()]);
    }

    /**
     * @return array<string, mixed>
     */
    private function withoutFacetFilters(array $filterOptions): array
    {
        foreach (TicketListFilterKind::cases() as $kind) {
            $filterOptions = $this->withoutFilter($filterOptions, $kind);
        }

        return $filterOptions;
    }

    /**
     * Returns a count of tickets per status bucket under every filter except
     * status itself. The counts describe what each status choice would show
     * for a given set of filter options in the UI.
     *
     * @param Builder<Ticket> $tickets
     * @param User|null $comparisonUser the user the developer and reporter filters compare against
     * @return array{unresolved: int, open: int, request: int, resolved: int, closed: int, quarantined: int, all: int}
     */
    public function getStateCounts(array $filterOptions, ?Builder $tickets = null, ?User $comparisonUser = null): array
    {
        $countQuery = $tickets === null ? Ticket::query() : clone $tickets;

        $countQuery = $this->applyFilters($countQuery, array_merge($filterOptions, ['status' => TicketListStatusFilter::All->value]), $comparisonUser);

        $countsByState = $countQuery
            ->reorder()
            ->select('state', DB::raw('count(*) as aggregate'))
            ->groupBy('state')
            ->pluck('aggregate', 'state')
            ->map(fn (mixed $count) => (int) $count);

        $countFor = fn (TicketState $state): int => $countsByState->get($state->value, 0);

        $open = $countFor(TicketState::Open);
        $request = $countFor(TicketState::Request);
        $unresolved = $open + $request;
        $resolved = $countFor(TicketState::Resolved);
        $closed = $countFor(TicketState::Closed);
        $quarantined = $countFor(TicketState::Quarantined);

        return [
            'unresolved' => $unresolved,
            'open' => $open,
            'request' => $request,
            'resolved' => $resolved,
            'closed' => $closed,
            'quarantined' => $quarantined,
            'all' => $unresolved + $resolved + $closed + $quarantined,
        ];
    }
}
