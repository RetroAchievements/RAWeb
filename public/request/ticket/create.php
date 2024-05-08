<?php

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetail)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

/** @var User $user */
$user = request()->user();

$input = Validator::validate(Arr::wrap(request()->post()), [
    'achievement' => 'required|integer|exists:Achievements,ID',
    'mode' => 'required|boolean',
    'issue' => 'required|integer|min:1|max:2',
    'description' => 'required|string|max:2000',
    'emulator' => 'required|string',
    'emulator_version' => 'required|string',
    'core' => 'nullable|string',
    'hash' => 'nullable|string',
]);

$achievementId = (int) $input['achievement'];

$note = $input['description'];
if (!empty($input['hash'])) {
    $note .= "\nRetroAchievements Hash: " . $input['hash'];
}
if (!empty($input['emulator'])) {
    $note .= "\nEmulator: " . $input['emulator'];
    if (!empty($input['core']) && ($input['emulator'] === 'RetroArch' || $input['emulator'] === 'RALibRetro')) {
        $note .= " (" . $input['core'] . ")";
    }
}
if (!empty($input['emulator_version'])) {
    $note .= "\nEmulator Version: " . $input['emulator_version'];
}

$ticketID = getExistingTicketID($user, $achievementId);
if ($ticketID !== 0) {
    return redirect(route('ticket.show', $ticketID))->withErrors(__('legacy.error.ticket_exists'));
}

$ticketID = submitNewTicket($user, $achievementId, (int) $input['issue'], (int) $input['mode'], $note);
if ($ticketID != 0) {
    return redirect(route('ticket.show', $ticketID))->with('success', __('legacy.success.submit'));
}

return back()->withErrors(__('legacy.error.ticket_create'));
