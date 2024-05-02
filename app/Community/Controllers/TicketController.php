<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Enums\TicketType;
use App\Http\Controller;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\Ticket;
use App\Support\Concerns\HandlesResources;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    use HandlesResources;

    public function resourceName(): string
    {
        return 'ticket';
    }

    public function index(): View
    {
        $this->authorize('viewAny', $this->resourceClass());

        return view('resource.index')
            ->with('resource', $this->resourceName());
    }

    public function indexForGame(Game $game): View
    {
        $this->authorize('viewAny', $this->resourceClass());

        return view('pages.ticket.index', [
            'game' => $game,
            'availableSelectFilters' => $this->getSelectFilters(),
            'filterOptions' => $this->getFilterOptions(),
        ]);
    }

    private function getFilterOptions(): array
    {
        $validatedData = request()->validate([
            'filter.status' => 'sometimes|string|in:all,unresolved,resolved',
            'filter.type' => 'sometimes|integer|min:0|max:2',
            'filter.achievement' => 'sometimes|string|in:all,core,unofficial',
            'filter.mode' => 'sometimes|string|in:all,hardcore,softcore,unspecified',
            'filter.developerType' => 'sometimes|string|in:all,active,junior,inactive',
        ]);
        return [
            'status' => $validatedData['filter']['status'] ?? 'unresolved',
            'type' => (int) $validatedData['filter']['type'] ?? 0,
            'achievement' => $validatedData['filter']['achievement'] ?? 'all',
            'mode' => $validatedData['filter']['mode'] ?? 'all',
            'developerType' => $validatedData['filter']['developerType'] ?? 'all',
        ];
    }

    private function getSelectFilters(bool $showDevType = true): array
    {

        $availableSelectFilters = [
            [
                'kind' => 'status',
                'label' => 'Ticket Status',
                'options' => [
                    'all' => 'All tickets',
                    'unresolved' => 'Unresolved tickets',
                    'resolved' => 'Resolved tickets',
                ],
            ],
            [
                'kind' => 'type',
                'label' => 'Ticket Type',
                'options' => [
                    0 => 'All',
                    TicketType::TriggeredAtWrongTime => TicketType::toString(TicketType::TriggeredAtWrongTime),
                    TicketType::DidNotTrigger => TicketType::toString(TicketType::DidNotTrigger),
                ],
            ],
            [
                'kind' => 'achievement',
                'label' => 'Achievement Type',
                'options' => [
                    'all' => 'All',
                    'core' => 'Core',
                    'unofficial' => 'Unofficial',
                ],
            ],
            [
                'kind' => 'mode',
                'label' => 'Mode',
                'options' => [
                    'all' => 'All',
                    'hardcore' => 'Hardcore',
                    'softcore' => 'Softcore',
                    'unspecified' => 'Unspecified',
                ],
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

    public function create(?Achievement $achievement = null): View
    {
        $this->authorize('create', Ticket::class);

        if ($achievement) {
            $_GET['i'] = $achievement->id;
        }

        return view('ticket.create')->with('achievement', $achievement);
    }

    public function store(Request $request): void
    {
    }

    public function show(Ticket $ticket): View
    {
        return view('pages.ticket.[ticket]')->with('ticket', $ticket);
    }

    public function edit(Ticket $ticket): void
    {
    }

    public function update(Request $request, Ticket $ticket): void
    {
    }

    public function destroy(Ticket $ticket): void
    {
    }
}
