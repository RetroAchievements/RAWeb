<?php

use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

// TODO do not allow GET requests, POST only
if (!ValidateGETChars('uig')) {
    echo "FAILED";
}

$lbID = requestInputQuery('i');
$gameID = requestInputQuery('g');

if (requestDeleteLB($lbID)) {
    return back()->with('success', __('legacy.success.delete'));
}

return back()->withErrors(__('legacy.error.error'));
