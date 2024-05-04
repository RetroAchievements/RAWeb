<?php

if (!authenticateFromCookie($user, $permissions)) {
    return back()->withErrors(__('legacy.error.error'));
}

if (cancelDeleteRequest($user->username)) {
    return back()->with('success', __('legacy.success.ok'));
}

return back()->withErrors(__('legacy.error.error'));
