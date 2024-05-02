<?php

use App\Enums\Permissions;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$userModel = User::firstWhere('User', $user);

if (!$userModel->can('manage', App\Models\ForumTopic::class)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'topic' => 'required|integer|exists:ForumTopic,ID',
    'permissions' => ['required', 'integer', Rule::in(Permissions::assignable())],
]);

if (updateTopicPermissions((int) $input['topic'], (int) $input['permissions'])) {
    return back()->with('success', __('legacy.success.ok'));
}

return back()->withErrors(__('legacy.error.error'));
