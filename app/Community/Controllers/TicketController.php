<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Http\Controller;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\Ticket;
use App\Platform\Services\TicketListService;
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

    public function indexForGame(Request $request, Game $game): View
    {
        $this->authorize('viewAny', $this->resourceClass());

        $ticketListService = new TicketListService();
        $selectFilters = $ticketListService->getSelectFilters();
        $filterOptions = $ticketListService->getFilterOptions($request);
        $tickets = $ticketListService->getTickets($filterOptions, Ticket::forGame($game));

        return view('pages.tickets.[game]', [
            'game' => $game,
            'tickets' => $tickets,
            'availableSelectFilters' => $selectFilters,
            'filterOptions' => $filterOptions,
            'totalTickets' => $ticketListService->totalTickets,
            'numFilteredTickets' => $ticketListService->numFilteredTickets,
        ]);
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
