<?php

use Illuminate\Support\Facades\Validator;
use RA\Permissions;

if (!authenticateFromCookie($actingUser, $permissions, $actingUserDetails, Permissions::Registered)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(request()->post(), [
    'user' => 'sometimes|string|exists:mysql_legacy.UserAccounts,User',
]);

$targetUser = $input['user'] ?? null;

if ($targetUser && $targetUser !== $actingUser && $permissions < Permissions::Admin) {
    return back()->withErrors(__('legacy.error.permissions'));
}

removeAvatar($targetUser ?? $actingUser);

return back()->with('success', __('legacy.success.ok'));
