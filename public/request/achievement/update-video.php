<?php

use Illuminate\Support\Facades\Validator;
use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    abort(401);
}

$input = Validator::validate(request()->post(), [
    'achievement' => 'required|integer',
    'video' => 'nullable|url',
]);

if (updateAchievementEmbedVideo($input['achievement'], $input['video'])) {
    return response()->json(['message' => __('legacy.success.ok')]);
}

abort(400);
