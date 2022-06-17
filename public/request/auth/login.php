<?php

use RA\ActivityType;

$user = requestInputPost('u');
$pass = requestInputPost('p');

if (!authenticateFromPassword($user, $pass)) {
    return back()->withErrors(__('legacy.error.credentials'));
}

postActivity($user, ActivityType::Login, null);

return back();
