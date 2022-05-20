<?php

use App\Site\Enums\Permissions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'game' => 'required|integer|exists:GameData,ID',
    'leaderboard' => 'nullable|integer|exists:LeaderboardDef,ID',
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
