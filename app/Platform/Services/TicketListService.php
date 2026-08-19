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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TicketListService
{
    public int $totalTickets = 0;
    public int $numFilteredTickets = 0;
    public int $perPage = 0;
    public int $pageNumber = 0;
    public int $totalPages = 0;

    public static function shouldShowResolverColumn(array $filterOptions): bool
    {
        return in_array($filterOptions['status'] ?? 'unresolved', ['all', 'resolved'], true);
    }

    /**
     * @return array{status: string, type: int, publishedStatus: string, mode: string, developerType: string, developer: string, reporter: string, emulator: string}
     */
    public function getFilterOptions(Request $request, TicketListStatusFilter $defaultStatus = TicketListStatusFilter::Unresolved): array
    {
        if ($this->perPage !== 0) {
            $validatedData = $request->validate([
                'page.number' => 'sometimes|integer|min:1',
            ]);
            $this->pageNumber = (int) ($validatedData['page']['number'] ?? 1);
        }

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

    public function getSelectFilters(
        bool $showStatus = true,
        bool $showPublishedStatus = true,
        bool $showDevType = true,
        bool $showDeveloper = false,
        bool $showReporter = false,
        ?int $systemId = null,
    ): array {
        $availableSelectFilters = [];

        if ($showStatus) {
            $availableSelectFilters[] = [
                'kind' => 'status',
                'label' => 'Ticket Status',
                'options' => [
                    'all' => 'All',
                    'unresolved' => 'Open',
                    'resolved' => 'Resolved',
                    'quarantined' => 'Quarantined',
                ],
            ];
        }

        $availableSelectFilters[] = [
            'kind' => 'type',
            'label' => 'Ticket Type',
            'options' => [
                0 => 'All',
                TicketType::TriggeredAtWrongTime->toLegacyInteger() => TicketType::TriggeredAtWrongTime->label(),
                TicketType::DidNotTrigger->toLegacyInteger() => TicketType::DidNotTrigger->label(),
            ],
        ];

        if ($showPublishedStatus) {
            $availableSelectFilters[] = [
                'kind' => 'publishedStatus',
                'label' => 'Published Status',
                'options' => [
                    'all' => 'All',
                    'published' => 'Published',
                    'unpublished' => 'Unpublished',
                ],
            ];
        }

        $availableSelectFilters[] = [
            'kind' => 'mode',
            'label' => 'Mode',
            'options' => [
                'all' => 'All',
                'hardcore' => 'Hardcore',
                'softcore' => 'Casual',
                'unspecified' => 'Unspecified',
            ],
        ];

        if ($showDevType) {
            $availableSelectFilters[] = [
                'kind' => 'developerType',
                'label' => 'Developer Type',
                'options' => [
                    'all' => 'All',
                    'active' => 'Active',
                    'junior' => 'Junior',
                    'inactive' => 'Inactive',
                ],
            ];
        }

        if ($showDeveloper) {
            $availableSelectFilters[] = [
                'kind' => 'developer',
                'label' => 'Developer',
                'options' => [
                    'all' => 'All',
                    'self' => 'Self',
                    'others' => 'Others',
                ],
            ];
        }

        if ($showReporter) {
            $availableSelectFilters[] = [
                'kind' => 'reporter',
                'label' => 'Reporter',
                'options' => [
                    'all' => 'All',
                    'self' => 'Self',
                    'others' => 'Others',
                ],
            ];
        }

        $emulatorOptions = ['all' => 'All'];
        if ($systemId) {
            $emulators = Emulator::forSystem($systemId);
        } else {
            $emulators = Emulator::whereIn('id', DB::table('system_emulators')->distinct('emulator_id')->pluck('emulator_id')->toArray());
        }
        foreach ($emulators->orderBy('name')->get() as $emulator) {
            $emulatorOptions[$emulator->name] = $emulator->name;
        }
        $emulatorOptions['unknown'] = 'Unknown';

        $availableSelectFilters[] = [
            'kind' => 'emulator',
            'label' => 'Emulator',
            'options' => $emulatorOptions,
        ];

        return $availableSelectFilters;
    }

    /**
     * @param Builder<Ticket> $tickets
     *
     * @return Collection<int, Ticket>
     */
    public function getTickets(array $filterOptions, ?Builder $tickets = null): Collection
    {
        return $this->buildQuery($filterOptions, $tickets)->orderBy('created_at', 'desc')->get();
    }

    /**
     * @param Builder<Ticket> $tickets
     *
     * @return Builder<Ticket>
     */
    public function buildQuery(array $filterOptions, ?Builder $tickets = null, ?User $comparisonUser = null): Builder
    {
        if ($tickets === null) {
            $tickets = Ticket::query();
        }

        $tickets->withLiveTicketable();

        $this->totalTickets = $tickets->count();

        // buildQuery keeps the legacy userId array contract for the Blade pages until they are gone.
        if ($comparisonUser === null && array_key_exists('userId', $filterOptions)) {
            $comparisonUser = User::withTrashed()->find($filterOptions['userId']);
        }

        $tickets = $this->applyFilters($tickets, $filterOptions, $comparisonUser);

        $this->numFilteredTickets = $tickets->count();

        if ($this->perPage > 0) {
            $this->totalPages = (int) ceil($this->numFilteredTickets / $this->perPage);

            if ($this->pageNumber < 1 || $this->pageNumber > $this->totalPages) {
                $this->pageNumber = 1;
            }

            $tickets->offset(($this->pageNumber - 1) * $this->perPage)->take($this->perPage);
        }

        return $tickets->with(['ticketable', 'author', 'reporter', 'resolver']);
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

            case TicketListStatusFilter::Request:
                $tickets->where('state', TicketState::Request);
                break;

            case TicketListStatusFilter::Resolved:
                $tickets->resolved();
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
     * Returns a count of tickets per status bucket under every filter except
     * status itself. The counts describe what each status choice would show
     * for a given set of filter options in the UI.
     *
     * @param Builder<Ticket> $tickets
     * @param User|null $comparisonUser the user the developer and reporter filters compare against
     * @return array{unresolved: int, request: int, resolved: int, quarantined: int, all: int}
     */
    public function getStateCounts(array $filterOptions, ?Builder $tickets = null, ?User $comparisonUser = null): array
    {
        $countQuery = $tickets === null ? Ticket::query() : clone $tickets;

        $countQuery->withLiveTicketable();

        $countQuery = $this->applyFilters($countQuery, array_merge($filterOptions, ['status' => TicketListStatusFilter::All->value]), $comparisonUser);

        $countsByState = $countQuery
            ->reorder()
            ->select('state', DB::raw('count(*) as aggregate'))
            ->groupBy('state')
            ->pluck('aggregate', 'state')
            ->map(fn (mixed $count) => (int) $count);

        $countFor = fn (TicketState $state): int => $countsByState->get($state->value, 0);

        $request = $countFor(TicketState::Request);
        $unresolved = $countFor(TicketState::Open) + $request;
        $resolved = $countFor(TicketState::Resolved) + $countFor(TicketState::Closed);
        $quarantined = $countFor(TicketState::Quarantined);

        return [
            'unresolved' => $unresolved,
            'request' => $request,
            'resolved' => $resolved,
            'quarantined' => $quarantined,
            'all' => $unresolved + $resolved + $quarantined,
        ];
    }
}
