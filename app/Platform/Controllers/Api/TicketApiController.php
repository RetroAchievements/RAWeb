<?php

namespace App\Platform\Controllers\Api;

use App\Community\Enums\CommentableType;
use App\Community\Enums\TicketAction;
use App\Community\Enums\TicketState;
use App\Http\Controller;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Platform\Actions\BuildTicketListAction;
use App\Platform\Actions\ChangeTicketStateAction;
use App\Platform\Actions\ClaimTicketAction;
use App\Platform\Actions\CreateTicketAction;
use App\Platform\Actions\ReleaseTicketClaimAction;
use App\Platform\Data\StoreTicketData;
use App\Platform\Requests\StoreTicketRequest;
use App\Platform\Requests\TicketListApiRequest;
use App\Platform\Requests\UpdateTicketStateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TicketApiController extends Controller
{
    public function index(TicketListApiRequest $request): JsonResponse
    {
        $scope = $request->getScope();
        $target = $scope->resolveTarget($request);

        $this->authorize('viewAny', [Ticket::class, $target instanceof User ? $target : null]);

        $result = (new BuildTicketListAction())->execute($scope, $target, $request, $request->user());

        return response()->json([
            'paginatedTickets' => $result['paginatedTickets'],
            'stateCounts' => $result['stateCounts'],
            'facetCounts' => $result['facetCounts'],
        ]);
    }

    public function store(
        StoreTicketRequest $request,
        CreateTicketAction $createTicket,
    ): JsonResponse {
        $data = StoreTicketData::fromRequest($request);

        $this->authorize('create', [Ticket::class, $data->ticketable]);

        // If the user has an existing open ticket, don't create a duplicate.
        $existingTicket = Ticket::where('reporter_id', $request->user()->id)
            ->where('ticketable_id', $data->ticketable->id)
            ->where('ticketable_type', 'achievement')
            ->whereNotIn('state', [TicketState::Closed, TicketState::Resolved])
            ->first();
        if ($existingTicket) {
            return response()->json([
                'message' => __('legacy.error.ticket_exists'),
                'ticketId' => $existingTicket->id,
            ], 409);
        }

        $ticket = $createTicket->execute($data, $request->user());

        return response()->json([
            'message' => __('legacy.success.submit'),
            'ticketId' => $ticket->id,
        ]);
    }

    public function updateState(
        UpdateTicketStateRequest $request,
        Ticket $ticket,
        ChangeTicketStateAction $changeState,
    ): JsonResponse {
        abort_if(!$ticket->ticketable, 404);

        $ticketAction = TicketAction::from($request->input('action'));
        $this->authorize('updateState', [$ticket, $ticketAction]);

        /** @var User $actingUser */
        $actingUser = $request->user();

        /** @var string|null $note */
        $note = $request->validated('note');

        $pickedCoveringTicketId = $ticketAction === TicketAction::CoveredByAnotherTicket
            ? $request->integer('coveringTicketId')
            : null;
        $closingComment = $this->composeClosingComment($note, $pickedCoveringTicketId);

        if ($closingComment !== null) {
            $this->authorize('create', [TicketComment::class, $ticket]);
            addArticleComment($actingUser->username, CommentableType::Ticket, $ticket->id, $closingComment);
        }

        $changeState->execute($ticket, $ticketAction, $actingUser);

        return response()->json(['message' => __('legacy.success.update')]);
    }

    private function composeClosingComment(?string $note, ?int $pickedCoveringTicketId): ?string
    {
        if (!$pickedCoveringTicketId) {
            return $note;
        }

        $coveringTicketUrl = route('ticket.show', ['ticket' => $pickedCoveringTicketId]);

        if ($note === null) {
            return $coveringTicketUrl;
        }

        // Skip appending the link when the note already points at the covering ticket.
        preg_match_all('~/ticket/(\d+)~', $note, $urlMatches);
        $mentionedTicketIds = array_map('intval', $urlMatches[1]);

        if (in_array($pickedCoveringTicketId, $mentionedTicketIds, true)) {
            return $note;
        }

        return "{$note}\n{$coveringTicketUrl}";
    }

    public function claim(Request $request, Ticket $ticket, ClaimTicketAction $claimTicketAction): Response
    {
        abort_if(!$ticket->ticketable, 404);

        $this->authorize('claim', $ticket);

        $claimTicketAction->execute($ticket, $request->user());

        return response()->noContent();
    }

    public function releaseClaim(Request $request, Ticket $ticket, ReleaseTicketClaimAction $releaseTicketClaimAction): Response
    {
        abort_if(!$ticket->ticketable, 404);

        $this->authorize('releaseClaim', $ticket);

        $releaseTicketClaimAction->execute($ticket, $request->user());

        return response()->noContent();
    }

    public function show(): void
    {
    }

    public function update(): void
    {
    }

    public function destroy(): void
    {
    }
}
