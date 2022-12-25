<?php

use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

try {
    UploadAvatar($user, request()->post('imageData'));
} catch (Exception $ex) {
    return response()->json(['message' => $ex->getMessage()], 400);
}

return response()->json(['message' => __('legacy.success.ok')]);
