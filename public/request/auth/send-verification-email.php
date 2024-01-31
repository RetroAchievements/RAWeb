<?php

use App\Models\User;

/** @var ?User $user */
$user = request()->user();

if (!$user) {
    return back()->withErrors(__('legacy.error.permissions'));
}

if (sendValidationEmail($user->User, $user->EmailAddress)) {
    return back()->with('message', __('legacy.email_validate'));
}

return back()->withErrors(__('legacy.error.account'));
