<?php

use App\Community\Enums\ActivityType;

$user = request()->post('u') ?? '';
$pass = request()->post('p') ?? '';

if (!authenticateFromPassword($user, $pass)) {
    return back()->withErrors(__('legacy.error.credentials'));
}

postActivity($user, ActivityType::Login);

return back();
