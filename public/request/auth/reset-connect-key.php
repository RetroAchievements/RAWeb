<?php

use App\Enums\Permissions;

if (!authenticateFromCookie($user, $permissions, Permissions::Registered)) {
    return back()->withErrors(__('legacy.error.error'));
}

generateAppToken($user->username, $token);

return back()->with('success', __('legacy.success.reset'));
