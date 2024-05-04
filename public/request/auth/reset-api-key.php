<?php

use App\Enums\Permissions;

if (!authenticateFromCookie($user, $permissions, Permissions::Registered)) {
    return back()->withErrors(__('legacy.error.error'));
}

generateAPIKey($user->username);

return back()->with('success', __('legacy.success.reset'));
