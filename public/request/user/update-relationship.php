<?php

use App\Community\Enums\UserRelationship;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

if (!authenticateFromCookie($user, $permissions, $userDetail)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'user' => 'required|string|exists:UserAccounts,display_name',
    'action' => ['required', 'integer', Rule::in(UserRelationship::cases())],
]);

/** @var User $senderUser */
$senderUser = Auth::user();
$targetUser = User::whereName($input['user'])->first();

if (!$targetUser) {
    return back()->withErrors(__('legacy.error.error'));
}

if (changeFriendStatus($senderUser, $targetUser, (int) $input['action']) !== 'error') {
    return back()->with('success', __('legacy.success.ok'));
}

return back()->withErrors(__('legacy.error.error'));
