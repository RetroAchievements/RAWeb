<?php

use App\Community\Enums\TicketAction;
use App\Community\Enums\TicketResolution;
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
    'ticket' => 'required|integer|exists:tickets,id',
    'action' => ['required', 'string', Rule::in(TicketAction::cases())],
]);

$ticketId = (int) $input['ticket'];
$ticket = Ticket::find($ticketId);
if (!$ticket) {
    return back()->withErrors(__('legacy.error.error'));
}

$resolution = null;
$ticketState = null;
switch ($input['action']) {
    case TicketAction::ClosedMistaken:
        $resolution = TicketResolution::MistakenReport;
        break;

    case TicketAction::Resolved:
        if ($permissions >= Permissions::Developer) {
            $resolution = TicketResolution::Fixed;
        }
        break;

    case TicketAction::Demoted:
        if ($permissions >= Permissions::Developer) {
            $resolution = TicketResolution::Demoted;
        }
        break;

    case TicketAction::NotEnoughInfo:
        if ($permissions >= Permissions::Developer) {
            $resolution = TicketResolution::NotEnoughInformation;
        }
        break;

    case TicketAction::WrongRom:
        if ($permissions >= Permissions::Developer) {
            $resolution = TicketResolution::WrongRom;
        }
        break;

    case TicketAction::Network:
        if ($permissions >= Permissions::Developer) {
            $resolution = TicketResolution::NetworkProblems;
        }
        break;

    case TicketAction::UnableToReproduce:
        if ($permissions >= Permissions::Developer) {
            $resolution = TicketResolution::UnableToReproduce;
        }
        break;

    case TicketAction::UnableToDebug:
        if ($permissions >= Permissions::Developer) {
            $resolution = TicketResolution::UnableToDebug;
        }
        break;

    case TicketAction::ClosedOther:
        if ($permissions >= Permissions::Developer) {
            $resolution = TicketResolution::Other;
        }
        break;

    case TicketAction::Request:
        if (!User::where('id', $ticket->reporter_id)->exists()) {
            return back()->withErrors(__('legacy.error.error'));
        }

        $ticketState = TicketState::Request;
        break;

    case TicketAction::Reopen:
        $ticketState = TicketState::Open;
        break;
}

$ticketState ??= $resolution?->finishedState();

if ($ticketState !== null && $ticketState !== $ticket->state) {
    $userModel = User::whereName($username)->first();
    if ($userModel
        && ($permissions >= Permissions::Developer || $userModel->id === $ticket->reporter_id)
    ) {
        updateTicket($userModel, $ticketId, $ticketState, $resolution);

        return back()->with('success', __('legacy.success.update'));
    }
}

return back()->withErrors(__('legacy.error.error'));
