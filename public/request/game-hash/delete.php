<?php

use Illuminate\Support\Facades\Validator;
use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    abort(401);
}

$input = Validator::validate(request()->post(), [
    'game' => 'required|integer|exists:mysql_legacy.GameData,ID',
    'hash' => 'required|string',
]);

if (!removeHash($user, (int) $input['game'], $input['hash'])) {
    abort(400);
}

return response()->json(['message' => __('legacy.success.delete')]);
