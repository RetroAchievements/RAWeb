<?php

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

// TODO do not allow GET requests, POST only
if (!ValidateGETChars("m")) {
    echo "FAILED";
}

$messageID = requestInputQuery('m');

if (DeleteMessage($user, $messageID)) {
    return back()->with('success', __('legacy.success.delete'));
}

return back()->withErrors(__('legacy.error.error'));
