<?php

use App\Legacy\Models\User;
use Illuminate\Support\Facades\Validator;

/** @var ?User $user */
$user = request()->user();
if (!$user) {
    return back()->withErrors(__('legacy.error.account'));
}
$username = $user->getAttribute('User');

$input = Validator::validate(request()->post(), [
    'password_current' => 'required',
    'password' => 'required|confirmed|min:8|different:username',
]);

// TODO check
$pass = $input['password_current'];
$newPass = $input['password'];

if (!authenticateFromPassword($username, $pass)) {
    return back()->withErrors(__('legacy.error.account'));
}

RemovePasswordResetToken($username);

if (changePassword($username, $newPass)) {
    generateAppToken($username, $tokenInOut);

    return back()->with('success', __('legacy.success.password_change'));
}

return back()->withErrors(__('legacy.error.error'));
