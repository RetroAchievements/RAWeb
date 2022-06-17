<?php

use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

// TODO do not allow GET requests, POST only
if (!ValidateGETChars("g")) {
    header("Location: " . config('app.url') . "/forum.php?e=invalidparams");
    exit;
}

$gameID = requestInputQuery('g');

if (generateGameForumTopic($user, $gameID, $forumTopicID)) {
    return redirect(url("/viewtopic.php?t=$forumTopicID"))->with('success', __('legacy.success.create'));
}

return back()->withErrors(__('legacy.error.error'));
