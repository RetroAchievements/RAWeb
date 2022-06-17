<?php

use Illuminate\Support\Facades\Validator;
use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    abort(401);
}

$input = Validator::validate(request()->post(), [
    'commentable_id' => 'required|integer',
    'comment' => 'required|string|max:60000',
]);

if (RemoveComment((int) $input['commentable_id'], (int) $input['comment'], $userDetails['ID'], $permissions)) {
    return response()->json(['message' => __('legacy.success.delete')]);
}

abort(400);
