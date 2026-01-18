<?php

use App\Models\PasswordResetToken;
use App\Models\User;
use App\Support\Rules\CtypeAlnum;
use App\Support\Rules\PasswordRules;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

$requestData = Arr::wrap(request()->post());

$targetUser = User::whereName($requestData['username'] ?? '')->first();

if (!$targetUser || $targetUser->isBanned()) {
    return back()->withErrors(__('legacy.error.token'));
}

$requestData['email'] = $targetUser->email;

$input = Validator::validate($requestData, [
    'username' => ['required', 'min:2', 'max:20', new CtypeAlnum()],
    'token' => 'required',
    'password' => PasswordRules::get(requireConfirmation: true),
]);

$passResetToken = $input['token'];
$newPass = $input['password'];

if (!PasswordResetToken::isValidForUser($targetUser, $passResetToken)) {
    return back()->withErrors(__('legacy.error.token'));
}

// Change the user's password and automatically log them in.
changePassword($targetUser->username, $newPass);
Auth::login($targetUser);

return redirect()->route('home')->with('success', __('legacy.success.password_change'));
