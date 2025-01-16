<?php

use App\Models\User;
use App\Support\Rules\CtypeAlnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->post()), [
    'username' => ['required', 'min:2', 'max:20', new CtypeAlnum()],
    'token' => 'required',
    'password' => 'required|confirmed|min:8|different:username',
]);

$passResetToken = $input['token'];
$newPass = $input['password'];

$targetUser = User::whereName($input['username'])->first();

if (!$targetUser || $targetUser->isBanned() || !isValidPasswordResetToken($targetUser->username, $passResetToken)) {
    return back()->withErrors(__('legacy.error.token'));
}

// Change the user's password and automatically log them in.
changePassword($targetUser->username, $newPass);
Auth::login($targetUser);

return redirect()->route('home')->with('success', __('legacy.success.password_change'));
