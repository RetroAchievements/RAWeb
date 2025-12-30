<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Community\Enums\TriggerTicketState;
use App\Http\Controller;
use App\Models\Achievement;
use App\Models\TriggerTicket;
use App\Platform\Actions\BuildTicketCreationDataAction;
use App\Support\Concerns\HandlesResources;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class TriggerTicketController extends Controller
{
    use HandlesResources;

    public function resourceName(): string
    {
        return 'trigger.ticket';
    }

    public function index(): View
    {
        $this->authorize('viewAny', $this->resourceClass());

        return view('resource.index')
            ->with('resource', $this->resourceName());
    }

    /*
     * TODO support all triggerables, eg: achievements, leaderboards, RP ...
     */
    public function create(
        Request $request,
        Achievement $achievement,
        BuildTicketCreationDataAction $buildTicketCreationData,
    ): InertiaResponse|HttpResponse {
        $this->authorize('createFor', [TriggerTicket::class, $achievement]);

        // A user can only have one ticket open at a time for a triggerable.
        // If they already have a ticket open, redirect them to the ticket's page.
        $existingTicket = TriggerTicket::where('reporter_id', $request->user()->id)
            ->where('ticketable_id', $achievement->id)
            ->where('ticketable_type', 'achievement')
            ->whereNotIn('state', [TriggerTicketState::Closed, TriggerTicketState::Resolved])
            ->first();
        if ($existingTicket) {
            // TODO stop using Inertia::location() after ticket.show is migrated to React
            return Inertia::location(route('ticket.show', ['triggerTicket' => $existingTicket->id]));
        }

        $props = $buildTicketCreationData->execute($achievement, $request->user());

        // If for some reason there are no hashes or emulators associated with a
        // game, then it isn't possible to create tickets for its triggerables.
        if (!count($props->gameHashes) || !count($props->emulators)) {
            // TODO stop using Inertia::location() after achievement.show is migrated to React
            return Inertia::location(route('achievement.show', $achievement->id));
        }

        return Inertia::render('achievement/[achievement]/tickets/create', $props);
    }

    public function store(Request $request): void
    {
    }

    public function show(TriggerTicket $ticket): void
    {
        // TODO currently uses Folio, convert to Inertia/React
    }

    public function edit(TriggerTicket $ticket): void
    {
    }

    public function update(Request $request, TriggerTicket $ticket): void
    {
    }

    public function destroy(TriggerTicket $ticket): void
    {
    }
}
