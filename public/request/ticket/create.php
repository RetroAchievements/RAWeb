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
    'extra' => 'nullable|string',
]);

$achievementId = (int) $input['achievement'];

$note = trim($input['description']);
$noteExtra = '';

if (!empty($input['extra'])) {
    $decoded = json_decode(base64_decode($input['extra']));
    if ($decoded) {
        $triggerRichPresence = $decoded->triggerRichPresence ?? '';
        if (!empty($triggerRichPresence)) {
            $noteExtra .= "\nRich Presence at time of trigger:\n$triggerRichPresence\n";
        }
    }
}
if (!empty($input['hash'])) {
    $noteExtra .= "\nRetroAchievements Hash: " . $input['hash'];
}
if (!empty($input['emulator'])) {
    $noteExtra .= "\nEmulator: " . $input['emulator'];
    if (!empty($input['core']) && ($input['emulator'] === 'RetroArch' || $input['emulator'] === 'RALibRetro')) {
        $noteExtra .= " (" . $input['core'] . ")";
    }
}
if (!empty($input['emulator_version'])) {
    $noteExtra .= "\nEmulator Version: " . $input['emulator_version'];
}

if (!empty($noteExtra)) {
    $note .= "\n" . $noteExtra;
}

$ticketID = getExistingTicketID($user, $achievementId);
if ($ticketID !== 0) {
    return redirect(route('ticket.show', ['ticket' => $ticketID]))->withErrors(__('legacy.error.ticket_exists'));
}

$ticketID = submitNewTicket($user, $achievementId, (int) $input['issue'], (int) $input['mode'], $note);
if ($ticketID != 0) {
    return redirect(route('ticket.show', ['ticket' => $ticketID]))->with('success', __('legacy.success.submit'));
}

return back()->withErrors(__('legacy.error.ticket_create'));
