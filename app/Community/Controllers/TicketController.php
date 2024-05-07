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

    public function __construct(
        protected TicketListService $ticketListService,
    ) {
    }

    public function resourceName(): string
    {
        return 'ticket';
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', $this->resourceClass());

        $this->ticketListService->perPage = 50;
        $selectFilters = $this->ticketListService->getSelectFilters();
        $filterOptions = $this->ticketListService->getFilterOptions($request);
        $tickets = $this->ticketListService->getTickets($filterOptions);

        return view('pages.tickets.index', [
            'tickets' => $tickets,
            'availableSelectFilters' => $selectFilters,
            'filterOptions' => $filterOptions,
            'totalTickets' => $this->ticketListService->totalTickets,
            'numFilteredTickets' => $this->ticketListService->numFilteredTickets,
            'currentPage' => $this->ticketListService->pageNumber,
            'totalPages' => $this->ticketListService->totalPages,
        ]);
    }

    public function indexForGame(Request $request, Game $game): View
    {
        $this->authorize('viewAny', $this->resourceClass());

        $this->ticketListService = new TicketListService();
        $selectFilters = $this->ticketListService->getSelectFilters();
        $filterOptions = $this->ticketListService->getFilterOptions($request);
        $tickets = $this->ticketListService->getTickets($filterOptions, Ticket::forGame($game));

        return view('pages.game.[game].tickets', [
            'game' => $game,
            'tickets' => $tickets,
            'availableSelectFilters' => $selectFilters,
            'filterOptions' => $filterOptions,
            'totalTickets' => $this->ticketListService->totalTickets,
            'numFilteredTickets' => $this->ticketListService->numFilteredTickets,
        ]);
    }

    public function indexForAchievement(Request $request, Achievement $achievement): View
    {
        $this->authorize('viewAny', $this->resourceClass());

        $this->ticketListService = new TicketListService();
        $selectFilters = $this->ticketListService->getSelectFilters(showDevType: false, showAchievementType: false);
        $filterOptions = $this->ticketListService->getFilterOptions($request);
        $tickets = $this->ticketListService->getTickets($filterOptions, Ticket::forAchievement($achievement));

        return view('pages.achievement.[achievement].tickets', [
            'achievement' => $achievement,
            'tickets' => $tickets,
            'availableSelectFilters' => $selectFilters,
            'filterOptions' => $filterOptions,
            'totalTickets' => $this->ticketListService->totalTickets,
            'numFilteredTickets' => $this->ticketListService->numFilteredTickets,
        ]);
    }

    public function indexForDeveloper(Request $request, User $user): View
    {
        $this->authorize('viewAny', $this->resourceClass());

        $this->ticketListService = new TicketListService();
        $this->ticketListService->perPage = 50;
        $selectFilters = $this->ticketListService->getSelectFilters(showDevType: false);
        $filterOptions = $this->ticketListService->getFilterOptions($request);
        $tickets = $this->ticketListService->getTickets($filterOptions, Ticket::forDeveloper($user));

        return view('pages.user.[user].tickets', [
            'user' => $user,
            'tickets' => $tickets,
            'availableSelectFilters' => $selectFilters,
            'filterOptions' => $filterOptions,
            'totalTickets' => $this->ticketListService->totalTickets,
            'numFilteredTickets' => $this->ticketListService->numFilteredTickets,
            'currentPage' => $this->ticketListService->pageNumber,
            'totalPages' => $this->ticketListService->totalPages,
        ]);
    }

    public function indexForDeveloperResolvedForOthers(Request $request, User $user): View
    {
        $this->authorize('viewAny', $this->resourceClass());

        $this->ticketListService = new TicketListService();
        $this->ticketListService->perPage = 50;
        $selectFilters = $this->ticketListService->getSelectFilters(showStatus: false);
        $filterOptions = $this->ticketListService->getFilterOptions($request);
        $filterOptions['status'] = 'all'; // will be filtered to Resolved below

        $ticketQuery = $user->resolvedTickets()->getQuery()
            ->where('ReportState', '=', TicketState::Resolved)
            ->where('reporter_id', '!=', $user->id)
            ->whereHas('achievement', function ($query) use ($user) {
                $query->where('user_id', '!=', $user->id);
            });

        $tickets = $this->ticketListService->getTickets($filterOptions, $ticketQuery);

        return view('pages.user.[user].tickets', [
            'pageTitle' => 'Tickets Resolved for Others',
            'user' => $user,
            'tickets' => $tickets,
            'availableSelectFilters' => $selectFilters,
            'filterOptions' => $filterOptions,
            'totalTickets' => $this->ticketListService->totalTickets,
            'numFilteredTickets' => $this->ticketListService->numFilteredTickets,
            'currentPage' => $this->ticketListService->pageNumber,
            'totalPages' => $this->ticketListService->totalPages,
        ]);
    }

    public function indexForReporterFeedback(Request $request, User $user): View
    {
        $this->authorize('viewAny', $this->resourceClass());

        $this->ticketListService = new TicketListService();
        $filterOptions = $this->ticketListService->getFilterOptions($request);

        $ticketQuery = Ticket::where('reporter_id', '=', $user->id)
            ->where('ReportState', '=', TicketState::Request);

        $tickets = $this->ticketListService->getTickets($filterOptions, $ticketQuery);

        return view('pages.user.[user].tickets', [
            'pageTitle' => 'Tickets Awaiting Feedback',
            'user' => $user,
            'tickets' => $tickets,
            'filterOptions' => $filterOptions,
            'totalTickets' => $this->ticketListService->totalTickets,
            'numFilteredTickets' => $this->ticketListService->numFilteredTickets,
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
