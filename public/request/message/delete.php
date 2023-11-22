<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'message' => 'required|integer|exists:user_message_chains,ID',
]);

return redirect(route('message.inbox'));

//return back()->withErrors(__('legacy.error.error'));
