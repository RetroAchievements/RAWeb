<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->post()), [
    'username' => 'required|string|exists:UserAccounts,User|alpha_num|min:4|max:20',
    'token' => 'required',
    'password' => 'required|confirmed|min:8|different:username',
]);

$user = $input['username'];
$passResetToken = $input['token'];
$newPass = $input['password'];

if (!isValidPasswordResetToken($user, $passResetToken)) {
    return back()->withErrors(__('legacy.error.token'));
}

RemovePasswordResetToken($user);

if (changePassword($user, $newPass)) {
    // Perform auto-login:
    if (authenticateFromCookie($user, $permissions, $userDetails)) {
        generateAppToken($user, $tokenInOut);
    }

    return back()->with('success', __('legacy.success.password_change'));
}

return back()->withErrors(__('legacy.error.error'));
