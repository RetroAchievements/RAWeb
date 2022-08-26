<?php

use Illuminate\Support\Facades\Validator;
use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    abort(401);
}

$input = Validator::validate(request()->post(), [
    'game' => 'required|integer|exists:mysql_legacy.GameData,ID',
    'hash' => 'required|string',
    'name' => 'required|string',
    'labels' => 'required|string',
]);

if (!updateHashDetails($user, (int) $input['game'], $input['hash'], $input['name'], $input['labels'])) {
    abort(400);
}

return response()->json(['message' => __('legacy.success.update')]);
