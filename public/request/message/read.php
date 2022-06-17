<?php

use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    abort(401);
}

$input = Validator::validate(request()->post(), [
    'message' => 'required|integer',
    'status' => 'required|integer|in:0,1',
]);

if (markMessageAsRead($user, $input['message'], $input['status'])) {
    if ((int) $input['status'] === 1) {
        return response()->json(['message' => __('legacy.success.ok')]);
    }
    return true;
}

abort(400);
