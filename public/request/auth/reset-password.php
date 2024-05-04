<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->post()), [
    'username' => 'required|string|exists:UserAccounts,User|alpha_num|max:20',
    'token' => 'required',
    'password' => 'required|confirmed|min:8|different:username',
]);

$username = $input['username'];
$passResetToken = $input['token'];
$newPass = $input['password'];

if (!isValidPasswordResetToken($username, $passResetToken)) {
    return back()->withErrors(__('legacy.error.token'));
}

changePassword($username, $newPass);

// Perform auto-login:
if (authenticateFromCookie($user, $permissions)) {
    generateAppToken($user->username, $tokenInOut);
}

return back()->with('success', __('legacy.success.password_change'));
