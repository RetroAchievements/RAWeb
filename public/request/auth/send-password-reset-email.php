<?php

use App\Models\PasswordResetToken;
use App\Models\User;
use App\Notifications\Auth\PasswordResetNotification;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

// Three attempts per IP per hour.
$key = 'password-reset:' . request()->ip();
if (RateLimiter::tooManyAttempts($key, 3)) {
    $seconds = RateLimiter::availableIn($key);

    return back()->withErrors(__('legacy.error.other'));
}
$oneHour = 60 * 60;
RateLimiter::hit($key, $oneHour);

$input = Validator::validate(Arr::wrap(request()->post()), [
    'username' => 'required',
]);

$targetUser = User::whereName($input['username'])->first();

if ($targetUser && !$targetUser->isBanned() && !empty($targetUser->email)) {
    $newToken = Str::random(20);

    // discard old tokens. only the most recent should be usable to actually reset the password.
    PasswordResetToken::where('user_id', $targetUser->id)->update(['token' => null]);

    PasswordResetToken::create([
        'user_id' => $targetUser->id,
        'token' => Hash::make($newToken),
        'ip_address' => request()->ip(),
    ]);

    $targetUser->notify(new PasswordResetNotification($newToken));
}

return back()->with('message', __('legacy.email_check'));
