<?php

namespace App\Platform\Controllers\Api;

use App\Community\Enums\TicketState;
use App\Http\Controller;
use App\Models\Ticket;
use App\Platform\Actions\CreateTicketAction;
use App\Platform\Data\StoreTicketData;
use App\Platform\Requests\StoreTicketRequest;
use Illuminate\Http\JsonResponse;

class TicketApiController extends Controller
{
    public function index(): void
    {
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
