<?php

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

$ulid = (string) Str::ulid();
$hashedPassword = Hash::make($pass);

$query = "INSERT INTO UserAccounts (ulid, User, display_name, Password, SaltedPass, EmailAddress, Permissions, RAPoints, fbUser, fbPrefs, cookie, appToken, appTokenExpiry, websitePrefs, LastLogin, LastActivityID, Motto, ContribCount, ContribYield, APIKey, APIUses, LastGameID, RichPresenceMsg, RichPresenceMsgDate, ManuallyVerified, UnreadMessageCount, TrueRAPoints, UserWallActive, PasswordResetToken, Untracked, email_backup)
VALUES ('$ulid', '$username', '$username', '$hashedPassword', '', '$email', 0, 0, 0, 0, '', '', NULL, 127, null, 0, '', 0, 0, '', 0, 0, '', NULL, 0, 0, 0, 1, NULL, false, '$email')";
$dbResult = s_mysql_query($query);

if (!$dbResult) {
    log_sql_fail();

    return back()->withErrors(__('legacy.error.error'));
}

// TODO let the framework handle registration events (sending out validation email, triggering notifications, ...)
// Registered::dispatch($user);

// Create an email validation token and send an email
$userModel = User::whereName($username)->first();
sendValidationEmail($userModel, $email);

return back()->with('message', __('legacy.email_validate'));
