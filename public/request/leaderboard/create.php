<?php

use Illuminate\Support\Facades\Validator;
use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(request()->post(), [
    'game' => 'required|integer|exists:mysql_legacy.GameData,ID',
    'leaderboard' => 'nullable|integer|exists:mysql_legacy.LeaderboardDef,ID',
    'amount' => 'required_with:leaderboard|integer|min:1|max:25',
]);

$gameID = (int) $input['game'];
$leaderboardID = $input['leaderboard'] ?? null;

// duplicate
if (!empty($leaderboardID)) {
    if (duplicateLeaderboard($gameID, (int) $leaderboardID, (int) $input['amount'], $user)) {
        return back()->with('success', __('legacy.success.ok'));
    }

    return back()->withErrors(__('legacy.error.error'));
}

$lbID = null;
if (submitNewLeaderboard($gameID, $lbID, $user)) {
    return redirect(url('leaderboardList.php?g=' . $gameID))->with('success', __('legacy.success.ok'));
}

return back()->withErrors(__('legacy.error.error'));
