<?php

use App\Community\Actions\VerifyTurnstileTokenAction;
use App\Enums\Permissions;
use App\Models\User;
use App\Support\Rules\PasswordRules;
use App\Support\Rules\ValidNewUsername;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

$input = Validator::validate(Arr::wrap(request()->post()), [
    'username' => ValidNewUsername::get(),
    'password' => PasswordRules::get(),
    'email' => 'required|email:filter,dns|confirmed|not_disposable_email',
    'terms' => 'accepted',
]);

$username = $input['username'];
$pass = $input['password'];
$email = $input['email'];

if (config('services.cloudflare.turnstile_secret_key')) {
    $isHuman = (new VerifyTurnstileTokenAction())->execute(
        request()->post('cf-turnstile-response'),
        request()->ip(),
    );

    if (!$isHuman) {
        return back()->withErrors(__('legacy.error.captcha'));
    }
}

$userModel = new User([
    'username' => $username,
    'display_name' => $username,
    'email' => $email,
    'Permissions' => Permissions::Unregistered,
    'preferences_bitfield' => 127,
    'points_hardcore' => 0,
    'points' => 0,
    'points_weighted' => 0,
]);
// these fields are not fillable, so we have to set them after initializing the User model
$userModel->password = Hash::make($pass);
$userModel->ulid = (string) Str::ulid();
$userModel->email_original = $email;
$userModel->unread_messages = 0;
$userModel->save();

// TODO let the framework handle registration events (sending out validation email, triggering notifications, ...)
// Registered::dispatch($user);

// Create an email validation token and send an email
sendValidationEmail($userModel, $email);

return back()->with('message', __('legacy.email_validate'));
