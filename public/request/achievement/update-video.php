<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use LegacyApp\Site\Enums\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    abort(401);
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'achievement' => 'required|integer',
    'video' => 'nullable|url',
]);

if (updateAchievementEmbedVideo((int) $input['achievement'], $input['video'])) {
    return response()->json(['message' => __('legacy.success.ok')]);
}

abort(400);
