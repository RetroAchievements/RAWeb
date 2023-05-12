<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use LegacyApp\Site\Enums\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'user' => 'sometimes|string|exists:mysql_legacy.UserAccounts,User',
]);

if ($input['user'] !== $user && $permissions < Permissions::Admin) {
    return back()->withErrors(__('legacy.error.permissions'));
}

if (recalculatePlayerPoints($input['user'])) {
    return back()->with('success', __('legacy.success.points_recalculate'));
}

return back()->withErrors(__('legacy.error.error'));
