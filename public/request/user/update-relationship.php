<?php

if (!authenticateFromCookie($user, $permissions, $userDetail)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

// TODO do not allow GET requests, POST only
if (!ValidateGETChars("fa")) {
    echo "FAILED";
}

$friend = requestInputQuery('f');
$action = requestInputQuery('a');

if (changeFriendStatus($user, $friend, $action) !== 'error') {
    return back()->with('success', __('legacy.success.ok'));
}

return back()->withErrors(__('legacy.error.error'));
