<?php

use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

UploadAvatar($user, requestInputPost('i'));

return response()->json(['message' => __('legacy.success.ok')]);
