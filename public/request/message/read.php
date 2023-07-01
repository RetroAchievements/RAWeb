<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    abort(401);
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'message' => 'required|integer|exists:Messages,ID',
    'status' => 'required|integer|in:0,1',
]);

if (markMessageAsRead($user, $input['message'], $input['status'])) {
    return true;
}

abort(400);
