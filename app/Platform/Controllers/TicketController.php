<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Community\Enums\TicketState;
use App\Http\Controller;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Actions\BuildTicketCreationDataAction;
use App\Platform\Actions\BuildTicketListAction;
use App\Platform\Data\AchievementData;
use App\Platform\Data\GameData;
use App\Platform\Data\TicketListPagePropsData;
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
        return $this->renderTicketList($request, 'tickets', null);
    }

    public function forGame(TicketListRequest $request, Game $game): InertiaResponse
    {
        return $this->renderTicketList($request, 'game/[game]/tickets', $game);
    }

    public function forAchievement(TicketListRequest $request, Achievement $achievement): InertiaResponse
    {
        return $this->renderTicketList($request, 'achievement/[achievement]/tickets/index', $achievement);
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

    private function renderTicketList(
        TicketListRequest $request,
        string $component,
        Game|Achievement|User|null $target,
    ): InertiaResponse {
        $this->authorize('viewAny', Ticket::class);

        $scope = $request->getScope();

        $action = new BuildTicketListAction();
        $result = $action->execute($scope, $target, $request);

        $props = new TicketListPagePropsData(
            scope: $scope,
            paginatedTickets: $result['paginatedTickets'],
            stateCounts: $result['stateCounts'],
            availableFilters: $action->getAvailableFilters($scope, $scope->systemId($target)),
            facetCounts: $result['facetCounts'],
            persistenceCookieName: $scope->persistenceCookieName(),
            persistedViewPreferences: $request->getCookiePreferences(),
            game: $target instanceof Game
                ? GameData::fromGame($target)->include('badgeUrl', 'system')
                : null,
            achievement: $target instanceof Achievement
                ? AchievementData::fromAchievement($target)->include('game', 'game.system')
                : null,
        );

        return Inertia::render($component, $props);
    }
}
