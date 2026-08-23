<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Community\Enums\TicketState;
use App\Http\Controller;
use App\Models\Achievement;
use App\Models\Ticket;
use App\Platform\Actions\BuildTicketCreationDataAction;
use App\Platform\Actions\BuildTicketListAction;
use App\Platform\Data\TicketListPagePropsData;
use App\Platform\Enums\TicketListScope;
use App\Platform\Requests\TicketListRequest;
use App\Support\Concerns\HandlesResources;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class TicketController extends Controller
{
    use HandlesResources;

    public function resourceName(): string
    {
        return 'ticket';
    }

    public function index(TicketListRequest $request): InertiaResponse
    {
        $this->authorize('viewAny', Ticket::class);

        $scope = TicketListScope::All;

        $action = new BuildTicketListAction();
        $result = $action->execute($scope, null, $request);

        $props = new TicketListPagePropsData(
            scope: $scope,
            paginatedTickets: $result['paginatedTickets'],
            stateCounts: $result['stateCounts'],
            availableFilters: $action->getAvailableFilters($scope),
            facetCounts: $result['facetCounts'],
            persistenceCookieName: 'datatable_view_preference_tickets_all',
            persistedViewPreferences: $request->getCookiePreferences(),
        );

        return Inertia::render('tickets', $props);
    }

    /*
     * TODO support all triggerables, eg: achievements, leaderboards, RP ...
     */
    public function create(
        Request $request,
        Achievement $achievement,
        BuildTicketCreationDataAction $buildTicketCreationData,
    ): InertiaResponse|HttpResponse {
        $this->authorize('createFor', [Ticket::class, $achievement]);

        // A user can only have one ticket open at a time for a triggerable.
        // If they already have a ticket open, redirect them to the ticket's page.
        $existingTicket = Ticket::where('reporter_id', $request->user()->id)
            ->where('ticketable_id', $achievement->id)
            ->where('ticketable_type', 'achievement')
            ->whereNotIn('state', [TicketState::Closed, TicketState::Resolved])
            ->first();
        if ($existingTicket) {
            // TODO stop using Inertia::location() after ticket.show is migrated to React
            return Inertia::location(route('ticket.show', ['ticket' => $existingTicket->id]));
        }

        $props = $buildTicketCreationData->execute($achievement, $request->user());

        // If for some reason there are no hashes or emulators associated with a
        // game, then it isn't possible to create tickets for its triggerables.
        if (!count($props->gameHashes) || !count($props->emulators)) {
            return redirect(route('achievement.show', ['achievement' => $achievement->id]));
        }

        return Inertia::render('achievement/[achievement]/tickets/create', $props);
    }

    public function store(Request $request): void
    {
    }

    public function show(Ticket $ticket): void
    {
        // TODO currently uses Folio, convert to Inertia/React
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
