<?php

use App\Site\Enums\Permissions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($actingUser, $permissions, $actingUserDetails, Permissions::Registered)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'user' => 'sometimes|string|exists:UserAccounts,User',
]);

$targetUser = $input['user'] ?? null;

if ($targetUser && $targetUser !== $actingUser && $permissions < Permissions::Moderator) {
    return back()->withErrors(__('legacy.error.permissions'));
}

removeAvatar($targetUser ?? $actingUser);

return back()->with('success', __('legacy.success.ok'));
