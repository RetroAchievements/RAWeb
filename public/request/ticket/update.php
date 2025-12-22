<?php

use App\Community\Enums\TicketAction;
use App\Community\Enums\TicketState;
use App\Enums\Permissions;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

if (!authenticateFromCookie($username, $permissions, $userDetail)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'ticket' => 'required|integer|exists:Ticket,ID',
    'action' => ['required', 'string', Rule::in(TicketAction::cases())],
]);

$ticketId = (int) $input['ticket'];
$ticket = Ticket::find($ticketId);
if (!$ticket) {
    return back()->withErrors(__('legacy.error.error'));
}

$reason = null;
$ticketState = null;
switch ($input['action']) {
    case TicketAction::ClosedMistaken:
        $ticketState = TicketState::Closed;
        $reason = "Mistaken report";
        break;

    case TicketAction::Resolved:
        if ($permissions >= Permissions::Developer) {
            $ticketState = TicketState::Resolved;
        }
        break;

    case TicketAction::Demoted:
        if ($permissions >= Permissions::Developer) {
            $ticketState = TicketState::Closed;
            $reason = "Demoted";
        }
        break;

    case TicketAction::NotEnoughInfo:
        if ($permissions >= Permissions::Developer) {
            $ticketState = TicketState::Closed;
            $reason = "Not enough information";
        }
        break;

    case TicketAction::WrongRom:
        if ($permissions >= Permissions::Developer) {
            $ticketState = TicketState::Closed;
            $reason = "Wrong ROM";
        }
        break;

    case TicketAction::Network:
        if ($permissions >= Permissions::Developer) {
            $ticketState = TicketState::Closed;
            $reason = "Network problems";
        }
        break;

    case TicketAction::UnableToReproduce:
        if ($permissions >= Permissions::Developer) {
            $ticketState = TicketState::Closed;
            $reason = "Unable to reproduce";
        }
        break;

    case TicketAction::UnableToDebug:
        if ($permissions >= Permissions::Developer) {
            $ticketState = TicketState::Closed;
            $reason = "Unable to debug due to no toolkit support";
        }
        break;

    case TicketAction::ClosedOther:
        if ($permissions >= Permissions::Developer) {
            $ticketState = TicketState::Closed;
            $reason = "See the comments";
        }
        break;

    case TicketAction::Request:
        if (!User::where('ID', $ticket->reporter_id)->exists()) {
            return back()->withErrors(__('legacy.error.error'));
        }

        $ticketState = TicketState::Request;
        break;

    case TicketAction::Reopen:
        $ticketState = TicketState::Open;
        break;
}

if ($ticketState !== null && $ticketState !== $ticket->ReportState) {
    $userModel = User::whereName($username)->first();
    if ($userModel
        && ($permissions >= Permissions::Developer || $userModel->id === $ticket->reporter_id)
    ) {
        updateTicket($userModel, $ticketId, $ticketState, $reason);

        return back()->with('success', __('legacy.success.update'));
    }
}

return back()->withErrors(__('legacy.error.error'));
