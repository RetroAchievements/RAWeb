<?php

use App\Site\Enums\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    return back()->withErrors(__('legacy.error.error'));
}

generateAppToken($user, $token);

return back()->with('success', __('legacy.success.reset'));
