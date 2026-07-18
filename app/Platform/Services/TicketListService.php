<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Community\Enums\TicketType;
use App\Enums\Permissions;
use App\Models\Achievement;
use App\Models\Emulator;
use App\Models\Leaderboard;
use App\Models\Ticket;
use App\Platform\Enums\TicketableType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function getFilterOptions(Request $request): array
    {
        if ($this->perPage !== 0) {
            $validatedData = $request->validate([
                'page.number' => 'sometimes|integer|min:1',
            ]);
            $this->pageNumber = (int) ($validatedData['page']['number'] ?? 1);
        }

        $validatedData = $request->validate([
            'filter.status' => 'sometimes|string|in:all,unresolved,resolved,quarantined',
            'filter.type' => 'sometimes|integer|min:0|max:2',
            'filter.publishedStatus' => 'sometimes|string|in:all,published,unpublished',
            'filter.mode' => 'sometimes|string|in:all,hardcore,softcore,unspecified',
            'filter.developerType' => 'sometimes|string|in:all,active,junior,inactive',
            'filter.developer' => 'sometimes|string|in:all,self,others',
            'filter.reporter' => 'sometimes|string|in:all,self,others',
            'filter.emulator' => 'sometimes|string',
        ]);

        return [
            'status' => $validatedData['filter']['status'] ?? 'unresolved',
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
    public function buildQuery(array $filterOptions, ?Builder $tickets = null): Builder
    {
        if ($tickets === null) {
            $tickets = Ticket::query();
        }

        // Don't include tickets where the ticketable is hard deleted.
        $tickets->whereHasMorph('ticketable', [Achievement::class, Leaderboard::class]);

        $this->totalTickets = $tickets->count();

        switch ($filterOptions['status']) {
            case 'unresolved':
                $tickets->open();
                break;

            case 'resolved':
                $tickets->resolved();
                break;

            case 'quarantined':
                $tickets->quarantined();
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

        if (array_key_exists('userId', $filterOptions)) {
            switch ($filterOptions['developer']) {
                case 'all':
                    break;

                case 'self':
                    $tickets->where('ticketable_author_id', '=', $filterOptions['userId']);
                    break;

                case 'others':
                    $tickets->where('ticketable_author_id', '!=', $filterOptions['userId']);
                    break;
            }

            switch ($filterOptions['reporter']) {
                case 'all':
                    break;

                case 'self':
                    $tickets->where('reporter_id', '=', $filterOptions['userId']);
                    break;

                case 'others':
                    $tickets->where('reporter_id', '!=', $filterOptions['userId']);
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
}
