<?php

use RA\Permissions;

if (!authenticateFromCookie($actingUser, $permissions, $actingUserDetails, Permissions::Registered)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$targetUser = requestInputPost('u');

if ($targetUser && $targetUser !== $actingUser && $permissions < Permissions::Admin) {
    return back()->withErrors(__('legacy.error.permissions'));
}

removeAvatar($targetUser ?? $actingUser);

return back()->with('success', __('legacy.success.ok'));
