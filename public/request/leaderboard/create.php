<?php

use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$gameID = requestInput('g');
$leaderboardID = requestInput('l');
$duplicateNumber = requestInput('n');

if (isset($leaderboardID) && isset($duplicateNumber)) {
    if (duplicateLeaderboard($gameID, $leaderboardID, $duplicateNumber, $user)) {
        return back()->with('success', __('legacy.success.ok'));
    }
} else {
    $lbID = null;
    if (submitNewLeaderboard($gameID, $lbID, $user)) {
        return back()->with('success', __('legacy.success.ok'));
    }
}

return back()->withErrors(__('legacy.error.error'));
