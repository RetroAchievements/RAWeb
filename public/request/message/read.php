<?php

use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    abort(401);
}

$input = Validator::validate(request()->post(), [
    'message' => 'required|integer|exists:mysql_legacy.Messages,ID',
    'status' => 'required|integer|in:0,1',
]);

if (markMessageAsRead($user, $input['message'], $input['status'])) {
    return true;
}

abort(400);
