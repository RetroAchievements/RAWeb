<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Community\Enums\TicketAction;
use App\Community\Enums\TicketState;
use App\Data\UserData;
use App\Data\UserPermissionsData;
use App\Http\Controller;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\System;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Actions\BuildTicketCreationDataAction;
use App\Platform\Actions\BuildTicketInboxPagePropsAction;
use App\Platform\Actions\BuildTicketListAction;
use App\Platform\Actions\BuildTicketShowPagePropsAction;
use App\Platform\Actions\ChangeTicketStateAction;
use App\Platform\Data\AchievementData;
use App\Platform\Data\GameData;
use App\Platform\Data\TicketListPagePropsData;
use App\Platform\Data\TicketNoLongerAProblemPagePropsData;
use App\Platform\Requests\TicketListRequest;
use App\Support\Concerns\HandlesResources;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
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
        abort_if(in_array($game->system_id, [System::Hubs, System::Events], true), 404);

        return $this->renderTicketList($request, 'game/[game]/tickets', $game);
    }

    public function forAchievement(TicketListRequest $request, Achievement $achievement): InertiaResponse
    {
        abort_if(in_array($achievement->game->system_id, [System::Hubs, System::Events], true), 404);

        return $this->renderTicketList($request, 'achievement/[achievement]/tickets/index', $achievement);
    }

    public function mine(Request $request): InertiaResponse
    {
        /**
         * We have a `user` param here for debugging purposes only. Nothing
         * exposes this in the UI.
         */
        $target = $request->filled('user')
            ? User::whereName($request->input('user'))->firstOrFail()
            : $request->user();

        $this->authorize('viewAny', [Ticket::class, $target]);

        $props = (new BuildTicketInboxPagePropsAction())->execute($target, $request->user());

        return Inertia::render('tickets/mine', $props);
    }

    public function forAssignee(TicketListRequest $request, User $user): InertiaResponse
    {
        $this->authorize('viewAny', [Ticket::class, $user]);

        return $this->renderTicketList($request, 'user/[user]/tickets/index', $user);
    }

    public function forReporter(TicketListRequest $request, User $user): InertiaResponse
    {
        $this->authorize('viewAny', [Ticket::class, $user]);

        return $this->renderTicketList($request, 'user/[user]/tickets/created', $user);
    }

    public function forAwaitingReporter(TicketListRequest $request, User $user): InertiaResponse
    {
        $this->authorize('viewAny', [Ticket::class, $user]);

        return $this->renderTicketList($request, 'user/[user]/tickets/feedback', $user);
    }

    public function forResolver(TicketListRequest $request, User $user): InertiaResponse
    {
        $this->authorize('viewAny', [Ticket::class, $user]);

        return $this->renderTicketList($request, 'user/[user]/tickets/resolved', $user);
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
            return redirect()->route('ticket.show', ['ticket' => $existingTicket->id]);
        }

        $props = $buildTicketCreationData->execute($achievement, $request->user());

        // If for some reason there are no hashes or emulators associated with a
        // game, then it isn't possible to create tickets for its triggerables.
        if (!count($props->gameHashes) || !count($props->emulators)) {
            return redirect(route('achievement.report-issue', ['achievement' => $achievement->id]));
        }

        return Inertia::render('achievement/[achievement]/tickets/create', $props);
    }

    public function store(Request $request): void
    {
    }

    public function show(
        Request $request,
        Ticket $ticket,
        BuildTicketShowPagePropsAction $buildTicketShowPagePropsAction,
    ): InertiaResponse {
        $this->authorize('view', $ticket);

        abort_if(!$ticket->ticketable, 404);

        /** @var User $user */
        $user = $request->user();

        $props = $buildTicketShowPagePropsAction->execute($ticket, $user);

        return Inertia::render('ticket/[ticket]', $props);
    }

    /**
     * The person who created the ticket will land here from a signed link in a
     * reminder email. Opening the link itself must _never_ close the ticket.
     * Mail scanners visit every link a message even if the user doesn't click on them.
     */
    public function noLongerAProblem(Ticket $ticket): InertiaResponse
    {
        abort_if(!$ticket->ticketable || !$ticket->reporter, 404);

        $ticketableModel = $ticket->getTicketableModel();

        return Inertia::render('ticket/[ticket]/no-longer-a-problem', new TicketNoLongerAProblemPagePropsData(
            ticketableTitle: $ticketableModel->title,
            gameTitle: $ticketableModel->getTicketableGame()->title,
            canClose: $this->canReporterClose($ticket),
            closeUrl: URL::temporarySignedRoute('ticket.no-longer-a-problem.close', now()->addDay(), ['ticket' => $ticket->id]),
        ));
    }

    public function closeAsNoLongerAProblem(Ticket $ticket, ChangeTicketStateAction $changeTicketStateAction): JsonResponse
    {
        abort_if(!$ticket->ticketable || !$ticket->reporter, 404);
        abort_if(!$this->canReporterClose($ticket), 409);

        $changeTicketStateAction->execute($ticket, TicketAction::ClosedMistaken, $ticket->reporter);

        return response()->json(['success' => true]);
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
        $result = $action->execute($scope, $target, $request, $request->user());

        $props = new TicketListPagePropsData(
            scope: $scope,
            paginatedTickets: $result['paginatedTickets'],
            stateCounts: $result['stateCounts'],
            availableFilters: $action->getAvailableFilters($scope, $request->user(), $scope->systemId($target)),
            facetCounts: $result['facetCounts'],
            defaultStatusFilter: $scope->defaultStatusFilter(),
            defaultSortParam: $scope->defaultSortParam(),
            hasStatusFilter: $scope->hasStatusFilter(),
            persistenceCookieName: $scope->persistenceCookieName(),
            persistedViewPreferences: $request->getCookiePreferences(),
            game: $target instanceof Game
                ? GameData::fromGame($target)->include('badgeUrl', 'system')
                : null,
            achievement: $target instanceof Achievement
                ? AchievementData::fromAchievement($target)->include('game', 'game.system')
                : null,
            user: $target instanceof User
                ? UserData::fromUser($target)->include('id')
                : null,
            can: UserPermissionsData::fromUser($request->user())->include('viewTriageSignals'),
        );

        return Inertia::render($component, $props);
    }

    private function canReporterClose(Ticket $ticket): bool
    {
        return
            $ticket->state->isOpen()
            && $ticket->reporter->can('updateState', [$ticket, TicketAction::ClosedMistaken]);
    }
}
