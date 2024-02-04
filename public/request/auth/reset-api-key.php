<?php

use App\Enums\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    return back()->withErrors(__('legacy.error.error'));
}

generateAPIKey($user);

return back()->with('success', __('legacy.success.reset'));
