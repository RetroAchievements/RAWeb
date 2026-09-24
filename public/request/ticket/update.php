<?php

use App\Community\Enums\TicketAction;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Actions\ChangeTicketStateAction;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

if (!authenticateFromCookie($username, $permissions, $userDetail)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'ticket' => 'required|integer|exists:tickets,id',
    'action' => ['required', 'string', Rule::enum(TicketAction::class)],
]);

$ticket = Ticket::find((int) $input['ticket']);
$action = TicketAction::from($input['action']);
$userModel = User::find($userDetail['id']);

if (!$ticket || !$userModel?->can('updateState', [$ticket, $action])) {
    return back()->withErrors(__('legacy.error.error'));
}

app(ChangeTicketStateAction::class)->execute($ticket, $action, $userModel);

return back()->with('success', __('legacy.success.update'));
