<?php

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

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

if ($targetUser && !$targetUser->isBanned()) {
    RequestPasswordReset($targetUser);
}

return back()->with('message', __('legacy.email_check'));
