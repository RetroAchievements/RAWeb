<?php

$user = requestInputQuery('u');

getAccountDetails($user, $userDetails);

if (!sendValidationEmail($user, $userDetails['EmailAddress'])) {
    return back()->withErrors(__('legacy.error.account'));
}

return back()->with('message', __('legacy.email_validate'));
