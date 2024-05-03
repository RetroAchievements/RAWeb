<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Community\Enums\AwardType;
use App\Community\Enums\TicketState;
use App\Community\Enums\TicketType;
use App\Community\Enums\UserGameListType;
use App\Enums\Permissions;
use App\Models\Game;
use App\Models\System;
use App\Models\Ticket;
use App\Models\User;
use App\Models\UserGameListEntry;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\UnlockMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;

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
        ]);

        return [
            'status' => $validatedData['filter']['status'] ?? 'unresolved',
            'type' => (int) ($validatedData['filter']['type'] ?? 0),
            'achievement' => $validatedData['filter']['achievement'] ?? 'all',
            'mode' => $validatedData['filter']['mode'] ?? 'all',
            'developerType' => $validatedData['filter']['developerType'] ?? 'all',
        ];
    }

    public function getSelectFilters(bool $showStatus = true, bool $showDevType = true): array
    {
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

        $availableSelectFilters[] = [
            'kind' => 'achievement',
            'label' => 'Achievement Type',
            'options' => [
                'all' => 'All',
                'core' => 'Core',
                'unofficial' => 'Unofficial',
            ],
        ];

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

        return $availableSelectFilters;
    }

    public function getTickets(array $filterOptions, Builder $tickets = null): Collection
    {
        return $this->buildQuery($filterOptions, $tickets)->orderBy('ReportedAt', 'DESC')->get();
    }

    public function buildQuery(array $filterOptions, Builder $tickets = null): Builder
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
                $tickets->whereHas('achievement', function($query) {
                    $query->whereHas('developer', function($query2) {
                        $query2->where('Permissions', '>=', Permissions::JuniorDeveloper);
                    });
                });
                break;
        
            case 'junior':
                $tickets->whereHas('achievement', function($query) {
                    $query->whereHas('developer', function($query2) {
                        $query2->where('Permissions', '=', Permissions::JuniorDeveloper);
                    });
                });
                break;
        
            case 'inactive':
                $tickets->whereHas('achievement', function($query) {
                    $query->whereHas('developer', function($query2) {
                        $query2->where('Permissions', '<', Permissions::JuniorDeveloper);
                    });
                });
                break;
        }
        
        $this->numFilteredTickets = $tickets->count();

        if ($this->perPage > 0) {
            $this->totalPages = (int) ceil($this->numFilteredTickets / $this->perPage);

            if ($this->pageNumber < 1 || $this->pageNumber > $this->totalPages) {
                $this->pageNumber = 1;
            }

            $tickets->offset(($this->pageNumber - 1) * $this->perPage)->take($this->perPage);
        }

        return $tickets->with(['achievement', 'reporter']);
    }
}
