<?php

use App\Community\Enums\UserRelationship;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

if (!authenticateFromCookie($senderUser, $permissions)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'user' => 'required|string|exists:UserAccounts,User',
    'action' => ['required', 'integer', Rule::in(UserRelationship::cases())],
]);

$targetUser = User::firstWhere('User', $input['user']);

if (!$targetUser) {
    return back()->withErrors(__('legacy.error.error'));
}

if (changeFriendStatus($senderUser, $targetUser, (int) $input['action']) !== 'error') {
    return back()->with('success', __('legacy.success.ok'));
}

return back()->withErrors(__('legacy.error.error'));
