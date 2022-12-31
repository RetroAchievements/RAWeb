<?php

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use LegacyApp\Community\Enums\UserRelationship;

if (!authenticateFromCookie($user, $permissions, $userDetail)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(request()->post(), [
    'user' => 'required|string|exists:mysql_legacy.UserAccounts,User',
    'action' => ['required', 'integer', Rule::in(UserRelationship::cases())],
]);

if (changeFriendStatus($user, $input['user'], (int) $input['action']) !== 'error') {
    return back()->with('success', __('legacy.success.ok'));
}

return back()->withErrors(__('legacy.error.error'));
