<?php

use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$gameID = requestInputQuery('g');

if (recalculateTrueRatio($gameID)) {
    return back()->with('success', __('legacy.success.points_recalculate'));
}

return back()->withErrors(__('legacy.error.error'));
