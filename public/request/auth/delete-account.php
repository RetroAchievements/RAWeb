<?php

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    return back()->withErrors(__('legacy.error.error'));
}

if (deleteRequest($user)) {
    return back()->with('success', __('legacy.success.ok'));
}

return back()->withErrors(__('legacy.error.error'));
