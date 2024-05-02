<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Http\Controller;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\Ticket;
use App\Models\User;
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

    public function index(Request $request): View
    {
        $this->authorize('viewAny', $this->resourceClass());

        $validatedData = $request->validate([
            'page.number' => 'sometimes|integer|min:1',
        ]);
        $pageNumber = (int) ($validatedData['page']['number'] ?? 1);
        $perPage = 50;

        $ticketListService = new TicketListService();
        $selectFilters = $ticketListService->getSelectFilters();
        $filterOptions = $ticketListService->getFilterOptions($request);
        $tickets = $ticketListService->getTickets($filterOptions, pageNumber: $pageNumber, perPage: $perPage);
        
        return view('pages.tickets.index', [
            'tickets' => $tickets,
            'availableSelectFilters' => $selectFilters,
            'filterOptions' => $filterOptions,
            'totalTickets' => $ticketListService->totalTickets,
            'numFilteredTickets' => $ticketListService->numFilteredTickets,
            'currentPage' => $pageNumber,
            'totalPages' => (int) ceil($ticketListService->numFilteredTickets / $perPage),
        ]);
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

    public function indexForDeveloper(Request $request, User $user): View
    {
        $this->authorize('viewAny', $this->resourceClass());

        $validatedData = $request->validate([
            'page.number' => 'sometimes|integer|min:1',
        ]);
        $pageNumber = (int) ($validatedData['page']['number'] ?? 1);
        $perPage = 50;

        $ticketListService = new TicketListService();
        $selectFilters = $ticketListService->getSelectFilters(showDevType: false);
        $filterOptions = $ticketListService->getFilterOptions($request);
        $tickets = $ticketListService->getTickets($filterOptions, Ticket::forDeveloper($user), pageNumber: $pageNumber, perPage: $perPage);

        return view('pages.tickets.[user]', [
            'user' => $user,
            'tickets' => $tickets,
            'availableSelectFilters' => $selectFilters,
            'filterOptions' => $filterOptions,
            'totalTickets' => $ticketListService->totalTickets,
            'numFilteredTickets' => $ticketListService->numFilteredTickets,
            'currentPage' => $pageNumber,
            'totalPages' => $ticketListService->numFilteredTickets > $perPage ? (int) ceil($ticketListService->numFilteredTickets / $perPage) : null,
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
