<?php

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

/** @var ?User $user */
$user = request()->user();
if (!$user) {
    return back()->withErrors(__('legacy.error.account'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'password_current' => 'required',
    'password' => 'required|confirmed|min:8|different:username',
]);

$username = $user->User;

if (!Hash::check($input['password_current'], $user->Password)) {
    return back()->withErrors(__('legacy.error.credentials'));
}

changePassword($username, $input['password']);
generateAppToken($username, $tokenInOut);

return back()->with('success', __('legacy.success.password_change'));
