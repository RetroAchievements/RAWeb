<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Enums\TicketState;
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

        $ticketListService = new TicketListService();
        $ticketListService->perPage = 50;
        $selectFilters = $ticketListService->getSelectFilters();
        $filterOptions = $ticketListService->getFilterOptions($request);
        $tickets = $ticketListService->getTickets($filterOptions);
        
        return view('pages.tickets.index', [
            'tickets' => $tickets,
            'availableSelectFilters' => $selectFilters,
            'filterOptions' => $filterOptions,
            'totalTickets' => $ticketListService->totalTickets,
            'numFilteredTickets' => $ticketListService->numFilteredTickets,
            'currentPage' => $ticketListService->pageNumber,
            'totalPages' => $ticketListService->totalPages,
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

        $ticketListService = new TicketListService();
        $ticketListService->perPage = 50;
        $selectFilters = $ticketListService->getSelectFilters(showDevType: false);
        $filterOptions = $ticketListService->getFilterOptions($request);
        $tickets = $ticketListService->getTickets($filterOptions, Ticket::forDeveloper($user));

        return view('pages.tickets.[user]', [
            'user' => $user,
            'tickets' => $tickets,
            'availableSelectFilters' => $selectFilters,
            'filterOptions' => $filterOptions,
            'totalTickets' => $ticketListService->totalTickets,
            'numFilteredTickets' => $ticketListService->numFilteredTickets,
            'currentPage' => $ticketListService->pageNumber,
            'totalPages' => $ticketListService->totalPages,
        ]);
    }

    public function indexForDeveloperResolvedForOthers(Request $request, User $user): View
    {
        $this->authorize('viewAny', $this->resourceClass());

        $ticketListService = new TicketListService();
        $ticketListService->perPage = 50;
        $selectFilters = $ticketListService->getSelectFilters(showStatus: false);
        $filterOptions = $ticketListService->getFilterOptions($request);
        $filterOptions['status'] = 'all'; // will be filtered to Resolved below

        $ticketQuery = $user->resolvedTickets()->getQuery()
            ->where('ReportState', '=', TicketState::Resolved)
            ->where('reporter_id', '!=', $user->id)
            ->whereHas('achievement', function($query) use ($user) {
                $query->where('user_id', '!=', $user->id);
            });

        $tickets = $ticketListService->getTickets($filterOptions, $ticketQuery);

        return view('pages.tickets.[user]', [
            'pageTitle' => 'Tickets Resolved for Others',
            'user' => $user,
            'tickets' => $tickets,
            'availableSelectFilters' => $selectFilters,
            'filterOptions' => $filterOptions,
            'totalTickets' => $ticketListService->totalTickets,
            'numFilteredTickets' => $ticketListService->numFilteredTickets,
            'currentPage' => $ticketListService->pageNumber,
            'totalPages' => $ticketListService->totalPages,
        ]);
    }

    public function mostReportedGames(Request $request): View
    {
        $this->authorize('viewAny', $this->resourceClass());
       
        return view('pages.tickets.most-reported-games');
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
