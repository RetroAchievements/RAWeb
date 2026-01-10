<?php

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
    'password' => PasswordRules::get(checkAgainstEmail: true),
    'email' => 'required|email:filter|confirmed|not_disposable_email',
    'terms' => 'accepted',
]);

$username = $input['username'];
$pass = $input['password'];
$email = $input['email'];

if (config('services.google.recaptcha_secret')) {
    if (empty($_POST['g-recaptcha-response'])) {
        return back()->withErrors(__('legacy.error.recaptcha'));
    }

    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = ['secret' => config('services.google.recaptcha_secret'), 'response' => $_POST['g-recaptcha-response']];

    // use key 'http' even if you send the request to https://...
    $context = stream_context_create([
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
        ],
    ]);
    $result = file_get_contents($url, false, $context);
    $resultJSON = json_decode($result, true, 512, JSON_THROW_ON_ERROR);

    if (array_key_exists('success', $resultJSON) && !$resultJSON['success']) {
        return back()->withErrors(__('legacy.error.recaptcha'));
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
