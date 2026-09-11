<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Community\Enums\TicketState;
use App\Community\Enums\TicketType;
use App\Models\Achievement;
use App\Models\Emulator;
use App\Models\Leaderboard;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Enums\LeaderboardState;
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
                $tickets->whereHas('author.roles', function ($query) {
                    $query->whereIn('name', [Role::DEVELOPER, Role::DEVELOPER_JUNIOR]);
                });
                break;

            case 'junior':
                $tickets->whereHas('author.roles', function ($query) {
                    $query->where('name', Role::DEVELOPER_JUNIOR);
                });
                break;

            case 'inactive':
                // For achievement tickets, also exclude any with an active maintainer.
                // Leaderboards don't have a maintainer concept, so author roles
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
                    $query->whereDoesntHave('roles', fn ($roles) => $roles->whereIn('name', [Role::DEVELOPER, Role::DEVELOPER_JUNIOR]));
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
        $widestFacetRowCount = $this->hasNonStatusFilters($filterOptions)
            ? $this->applyFilters(clone $tickets, $this->withoutFacetFilters($filterOptions), $comparisonUser)->count()
            : $filteredTotal;

        if ($widestFacetRowCount !== null && $widestFacetRowCount > self::MAX_FACET_COUNT_ROWS) {
            return [];
        }

        $counts = [];
        foreach ($kinds as $kind) {
            if ($kind === TicketListFilterKind::Developer || $kind === TicketListFilterKind::Reporter) {
                continue;
            }

            $query = $this->applyFilters(clone $tickets, $this->withoutFilter($filterOptions, $kind), $comparisonUser)->reorder();
            $counts[$kind->value] = match ($kind) {
                TicketListFilterKind::Type => $this->countGroupedFacet(
                    $query,
                    $kind,
                    'tickets.type',
                    fn (?string $value) => match ($value) {
                        TicketType::TriggeredAtWrongTime->value => (string) TicketType::TriggeredAtWrongTime->toLegacyInteger(),
                        TicketType::DidNotTrigger->value => (string) TicketType::DidNotTrigger->toLegacyInteger(),
                        default => null,
                    },
                ),
                TicketListFilterKind::Mode => $this->countGroupedFacet(
                    $query,
                    $kind,
                    'tickets.hardcore',
                    fn (?string $value) => match ($value) {
                        null => 'unspecified',
                        '1' => 'hardcore',
                        default => 'softcore',
                    },
                ),
                TicketListFilterKind::Emulator => $this->countGroupedFacet(
                    $query,
                    $kind,
                    'tickets.emulator_id',
                    fn (?string $value) => $value === null
                        ? 'unknown'
                        : ($this->emulatorNamesById()[(int) $value] ?? null),
                ),
                TicketListFilterKind::PublishedStatus => $this->countPublishedStatusFacet($query),
                TicketListFilterKind::DeveloperType => $this->countDeveloperTypeFacet($query),
            };
        }

        return $counts;
    }

    /**
     * @param Builder<Ticket> $query
     * @param callable(?string): ?string $toFilterValue
     * @return array<string, int>
     */
    private function countGroupedFacet(
        Builder $query,
        TicketListFilterKind $kind,
        string $column,
        callable $toFilterValue,
    ): array {
        $rows = $query->select($column . ' as facet_value', DB::raw('count(*) as aggregate'))
            ->groupBy('facet_value')
            ->toBase()->get();

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
     * @param Builder<Ticket> $query
     * @return array<string, int>
     */
    private function countPublishedStatusFacet(Builder $query): array
    {
        $kind = TicketListFilterKind::PublishedStatus;

        // Aliases keep joined columns from conflicting with unqualified scope columns such as id and state.
        $rows = $query->selectRaw('count(*) as aggregate')
            ->leftJoinSub(Achievement::select('id as facet_achievement_id', 'is_promoted'), 'facet_achievement', fn ($join) => $join
                ->on('facet_achievement_id', '=', 'tickets.ticketable_id')
                ->where('tickets.ticketable_type', TicketableType::Achievement->value))
            ->leftJoinSub(Leaderboard::select('id as facet_leaderboard_id', 'state as facet_state'), 'facet_leaderboard', fn ($join) => $join
                ->on('facet_leaderboard_id', '=', 'tickets.ticketable_id')
                ->where('tickets.ticketable_type', TicketableType::Leaderboard->value))
            ->selectRaw('coalesce(is_promoted, facet_state != ?) as published', [LeaderboardState::Unpromoted->value])
            ->groupBy('published')->toBase()->get();

        $counts = array_fill_keys($kind->values(), 0);
        foreach ($rows as $row) {
            $counts[$kind->noFilterValue()] += (int) $row->aggregate;
            if ($row->published !== null) {
                $counts[$row->published ? 'published' : 'unpublished'] += (int) $row->aggregate;
            }
        }

        return $counts;
    }

    /**
     * @param Builder<Ticket> $query
     * @return array<string, int>
     */
    private function countDeveloperTypeFacet(Builder $query): array
    {
        $kind = TicketListFilterKind::DeveloperType;
        $developerRoles = DB::table('auth_model_roles')
            ->join('auth_roles', 'auth_roles.id', '=', 'auth_model_roles.role_id')
            ->where('model_type', (new User())->getMorphClass())
            ->whereIn('auth_roles.name', [Role::DEVELOPER, Role::DEVELOPER_JUNIOR])
            ->select('model_id as facet_author_id')
            ->selectRaw('max(auth_roles.name = ?) as facet_junior', [Role::DEVELOPER_JUNIOR])
            ->groupBy('model_id');

        $rows = $query->selectRaw('count(*) as aggregate')
            ->withExists('author')
            ->leftJoinSub($developerRoles, 'facet_roles', 'facet_author_id', '=', 'tickets.ticketable_author_id')
            ->selectRaw('(facet_author_id is not null) as active, coalesce(facet_junior, 0) as junior')
            ->withExists(['achievement as unmaintained' => fn ($achievement) => $achievement
                ->where('tickets.ticketable_type', TicketableType::Achievement->value)
                ->whereDoesntHave('activeMaintainer')])
            ->addSelect('tickets.ticketable_type')
            ->groupBy('author_exists', 'active', 'junior', 'unmaintained', 'tickets.ticketable_type')->toBase()->get();

        $counts = array_fill_keys($kind->values(), 0);
        foreach ($rows as $row) {
            $count = (int) $row->aggregate;
            $counts[$kind->noFilterValue()] += $count;

            if (!$row->author_exists) {
                continue;
            }

            if ($row->active) {
                $counts['active'] += $count;
                if ($row->junior) {
                    $counts['junior'] += $count;
                }
            } elseif ($row->ticketable_type === TicketableType::Leaderboard->value || $row->unmaintained) {
                $counts['inactive'] += $count;
            }
        }

        return $counts;
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
