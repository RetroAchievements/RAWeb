<?php

use App\Legacy\Models\User;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetail)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

/** @var User $user */
$user = request()->user();

$input = Validator::validate(request()->post(), [
    'achievement' => 'required|integer|exists:mysql_legacy.Achievements,ID',
    'mode' => 'required|boolean',
    'issue' => 'required|integer|min:1|max:2',
    'note' => 'sometimes|string|max:2',
]);

$achievementID = (int) $input['achievement'];
$problemType = $input['issue'];
$hardcore = (int) $input['mode'];

// TODO untangle $_POST['note']

$note = null;
if (isset($_POST['note'])) {
    $appendNote = $_POST['note']['description'];

    if (!empty(trim($_POST['note']['checksum']))) {
        $appendNote .= "\nRetroAchievements Hash: " . $_POST['note']['checksum'];
    }

    if (!empty(trim($_POST['note']['emulator']))) {
        $appendNote .= "\nEmulator: " . $_POST['note']['emulator'];

        if ($_POST['note']['emulator'] == "RetroArch" || $_POST['note']['emulator'] == "RALibRetro") {
            $appendNote .= " (" . $_POST['note']['core'] . ")";
        }
    }

    if (!empty(trim($_POST['note']['emulatorVersion']))) {
        $appendNote .= "\nEmulator Version: " . $_POST['note']['emulatorVersion'];
    }

    $note = $appendNote;
}

if (submitNewTickets($user, $achievementID, $problemType, $hardcore, $note, $msgOut)) {
    return back()->with('success', __('legacy.success.ok'));
}

return back()->withErrors(__('legacy.error.error'));
