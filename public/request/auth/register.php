<?php

use App\Enums\Permissions;
use App\Models\User;
use App\Support\Rules\ValidNewUsername;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

$input = Validator::validate(Arr::wrap(request()->post()), [
    'username' => ValidNewUsername::get(),
    'password' => 'required|min:8|different:username',
    'email' => 'required|email:filter|confirmed',
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
    'User' => $username,
    'display_name' => $username,
    'EmailAddress' => $email,
    'Permissions' => Permissions::Unregistered,
    'websitePrefs' => 127,
    'RAPoints' => 0,
    'RASoftcorePoints' => 0,
    'TrueRAPoints' => 0,
]);
// these fields are not fillable, so we have to set them after initializing the User model
$userModel->Password = Hash::make($pass);
$userModel->ulid = (string) Str::ulid();
$userModel->email_backup = $email;
$userModel->fbUser = 0;
$userModel->fbPrefs = 0;
$userModel->UnreadMessageCount = 0;
$userModel->save();

// TODO let the framework handle registration events (sending out validation email, triggering notifications, ...)
// Registered::dispatch($user);

// Create an email validation token and send an email
sendValidationEmail($userModel, $email);

return back()->with('message', __('legacy.email_validate'));
