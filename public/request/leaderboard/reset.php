<?php

use RA\ArticleType;
use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

// TODO do not allow GET requests, POST only
if (!ValidateGETChars("ui")) {
    echo "FAILED! (POST)";
}

$lbid = requestInputQuery('i');

requestResetLB($lbid);

$commentText = 'reset all entries for this leaderboard';
addArticleComment("Server", ArticleType::Leaderboard, $lbid, "\"$user\" $commentText.", $user);

return back()->with('success', __('legacy.success.ok'));
