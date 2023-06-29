<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($username, $permissions, $userDetail)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'recipient' => 'required|string|exists:UserAccounts,User',
    'subject' => 'required|string|max:255',
    'message' => 'required|string|max:60000',
]);

$recipient = $input['recipient'];

if (isUserBlocking($recipient, $username)) {
    return redirect(url('inbox.php?s=1'))->with('success', __('legacy.success.message_send'));
}

if (CreateNewMessage($username, $recipient, $input['subject'], $input['message'])) {
    return redirect(url('inbox.php?s=1'))->with('success', __('legacy.success.message_send'));
}

return back()->withErrors(__('legacy.error.error'));
