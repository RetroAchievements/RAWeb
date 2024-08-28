<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Community\Enums\TicketType;
use App\Enums\Permissions;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class TicketListService
{
    public int $totalTickets = 0;
    public int $numFilteredTickets = 0;
    public int $perPage = 0;
    public int $pageNumber = 0;
    public int $totalPages = 0;

    public function getFilterOptions(Request $request): array
    {
        if ($this->perPage !== 0) {
            $validatedData = $request->validate([
                'page.number' => 'sometimes|integer|min:1',
            ]);
            $this->pageNumber = (int) ($validatedData['page']['number'] ?? 1);
        }

        $validatedData = $request->validate([
            'filter.status' => 'sometimes|string|in:all,unresolved,resolved',
            'filter.type' => 'sometimes|integer|min:0|max:2',
            'filter.achievement' => 'sometimes|string|in:all,core,unofficial',
            'filter.mode' => 'sometimes|string|in:all,hardcore,softcore,unspecified',
            'filter.developerType' => 'sometimes|string|in:all,active,junior,inactive',
            'filter.developer' => 'sometimes|string|in:all,self,others',
            'filter.reporter' => 'sometimes|string|in:all,self,others',
        ]);

        return [
            'status' => $validatedData['filter']['status'] ?? 'unresolved',
            'type' => (int) ($validatedData['filter']['type'] ?? 0),
            'achievement' => $validatedData['filter']['achievement'] ?? 'all',
            'mode' => $validatedData['filter']['mode'] ?? 'all',
            'developerType' => $validatedData['filter']['developerType'] ?? 'all',
            'developer' => $validatedData['filter']['developer'] ?? 'all',
            'reporter' => $validatedData['filter']['reporter'] ?? 'all',
        ];
    }

    public function getSelectFilters(
        bool $showStatus = true,
        bool $showAchievementType = true,
        bool $showDevType = true,
        bool $showDeveloper = false,
        bool $showReporter = false
    ): array {
        $availableSelectFilters = [];

        if ($showStatus) {
            $availableSelectFilters[] = [
                'kind' => 'status',
                'label' => 'Ticket Status',
                'options' => [
                    'all' => 'All tickets',
                    'unresolved' => 'Open tickets',
                    'resolved' => 'Resolved tickets',
                ],
            ];
        }

        $availableSelectFilters[] = [
            'kind' => 'type',
            'label' => 'Ticket Type',
            'options' => [
                0 => 'All',
                TicketType::TriggeredAtWrongTime => TicketType::toString(TicketType::TriggeredAtWrongTime),
                TicketType::DidNotTrigger => TicketType::toString(TicketType::DidNotTrigger),
            ],
        ];

        if ($showAchievementType) {
            $availableSelectFilters[] = [
                'kind' => 'achievement',
                'label' => 'Achievement Type',
                'options' => [
                    'all' => 'All',
                    'core' => 'Core',
                    'unofficial' => 'Unofficial',
                ],
            ];
        }

        $availableSelectFilters[] = [
            'kind' => 'mode',
            'label' => 'Mode',
            'options' => [
                'all' => 'All',
                'hardcore' => 'Hardcore',
                'softcore' => 'Softcore',
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

        return $availableSelectFilters;
    }

    /**
     * @param Builder<Ticket> $tickets
     *
     * @return Collection<int, Ticket>
     */
    public function getTickets(array $filterOptions, ?Builder $tickets = null): Collection
    {
        return $this->buildQuery($filterOptions, $tickets)->orderBy('ReportedAt', 'DESC')->get();
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

        $this->totalTickets = $tickets->count();

        switch ($filterOptions['status']) {
            case 'unresolved':
                $tickets->unresolved();
                break;

            case 'resolved':
                $tickets->resolved();
                break;
        }

        if ($filterOptions['type'] > 0) {
            $tickets->where('ReportType', $filterOptions['type']);
        }

        switch ($filterOptions['achievement']) {
            case 'core':
                $tickets->officialCore();
                break;

            case 'unofficial':
                $tickets->unofficial();
                break;
        }

        switch ($filterOptions['mode']) {
            case 'hardcore':
                $tickets->where('Hardcore', 1);
                break;

            case 'softcore':
                $tickets->where('Hardcore', 0);
                break;

            case 'unspecified':
                $tickets->whereNull('Hardcore');
                break;
        }

        switch ($filterOptions['developerType']) {
            case 'active':
                $tickets->whereHas('achievement', function ($query) {
                    $query->whereHas('developer', function ($query2) {
                        $query2->where('Permissions', '>=', Permissions::JuniorDeveloper);
                    });
                });
                break;

            case 'junior':
                $tickets->whereHas('achievement', function ($query) {
                    $query->whereHas('developer', function ($query2) {
                        $query2->where('Permissions', '=', Permissions::JuniorDeveloper);
                    });
                });
                break;

            case 'inactive':
                $tickets->whereHas('achievement', function ($query) {
                    $query->whereHas('developer', function ($query2) {
                        $query2->where('Permissions', '<', Permissions::JuniorDeveloper);
                    });
                });
                break;
        }

        if (array_key_exists('userId', $filterOptions)) {
            switch ($filterOptions['developer']) {
                case 'all':
                    break;

                case 'self':
                    $tickets->whereHas('achievement', function ($query) use ($filterOptions) {
                        $query->where('user_id', '=', $filterOptions['userId']);
                    });
                    break;

                case 'others':
                    $tickets->whereHas('achievement', function ($query) use ($filterOptions) {
                        $query->where('user_id', '!=', $filterOptions['userId']);
                    });
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

        $this->numFilteredTickets = $tickets->count();

        if ($this->perPage > 0) {
            $this->totalPages = (int) ceil($this->numFilteredTickets / $this->perPage);

            if ($this->pageNumber < 1 || $this->pageNumber > $this->totalPages) {
                $this->pageNumber = 1;
            }

            $tickets->offset(($this->pageNumber - 1) * $this->perPage)->take($this->perPage);
        }

        return $tickets->with(['achievement', 'author', 'reporter', 'resolver']);
    }
}
