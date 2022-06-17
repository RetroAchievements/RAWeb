<?php

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    abort(401);
}

$gameID = requestInputPost('g', null, 'integer');
$achID = requestInputPost('a', null, 'integer');

if (!empty($achID) && resetSingleAchievement($user, $achID)) {
    return response()->json(['message' => __('legacy.success.reset')]);
}

if (!empty($gameID) && resetAchievements($user, $gameID) > 0) {
    return response()->json(['message' => __('legacy.success.reset')]);
}

abort(400);
