<?php

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->post()), [
    'username' => 'required|string|exists:UserAccounts,User|alpha_num|max:20',
    'token' => 'required',
    'password' => 'required|confirmed|min:8|different:username',
]);

$passResetToken = $input['token'];
$newPass = $input['password'];

$targetUser = User::firstWhere('User', $input['username']);

if (!$targetUser || !isValidPasswordResetToken($targetUser->username, $passResetToken)) {
    return back()->withErrors(__('legacy.error.token'));
}

// Change the user's password and automatically log them in.
changePassword($targetUser->username, $newPass);
Auth::login($targetUser);

return redirect()->route('home')->with('success', __('legacy.success.password_change'));
