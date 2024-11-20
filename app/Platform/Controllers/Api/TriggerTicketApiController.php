<?php

namespace App\Platform\Controllers\Api;

use App\Community\Enums\TicketState;
use App\Http\Controller;
use App\Models\Ticket;
use App\Models\TriggerTicket;
use App\Platform\Actions\CreateTriggerTicketAction;
use App\Platform\Data\StoreTriggerTicketData;
use App\Platform\Requests\StoreTriggerTicketRequest;
use Illuminate\Http\JsonResponse;

class TriggerTicketApiController extends Controller
{
    public function index(): void
    {
    }

    public function store(
        StoreTriggerTicketRequest $request,
        CreateTriggerTicketAction $createTriggerTicket,
    ): JsonResponse {
        $data = StoreTriggerTicketData::fromRequest($request);

        $this->authorize('create', [TriggerTicket::class, $data->ticketable]);

        // If the user has an existing open ticket, don't create a duplicate.
        $existingTicket = Ticket::whereReporterId($request->user()->id)
            ->where('AchievementID', $data->ticketable->id)
            ->whereNotIn('ReportState', [TicketState::Closed, TicketState::Resolved])
            ->first();
        if ($existingTicket) {
            return response()->json([
                'message' => __('legacy.error.ticket_exists'),
                'ticketId' => $existingTicket->id,
            ], 409);
        }

        $ticket = $createTriggerTicket->execute($data, $request->user());

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
