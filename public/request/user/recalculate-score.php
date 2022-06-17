<?php

use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$targetUser = request()->post('u');

if ($targetUser !== $user && $permissions < Permissions::Admin) {
    return back()->withErrors(__('legacy.error.permissions'));
}

if (recalculatePlayerPoints($user)) {
    return back()->with('success', __('legacy.success.points_recalculate'));
}

return back()->withErrors(__('legacy.error.error'));
