<?php

use App\Enums\Permissions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($actingUser, $permissions, Permissions::Registered)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'user' => 'sometimes|string|exists:UserAccounts,User',
]);

$targetUsername = $input['user'] ?? null;

// TODO use a policy
if ($targetUsername && $targetUsername !== $actingUser->username && $permissions < Permissions::Moderator) {
    return back()->withErrors(__('legacy.error.permissions'));
}

removeAvatar($targetUsername ?? $actingUser->username);

return back()->with('success', __('legacy.success.ok'));
