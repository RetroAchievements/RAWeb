<?php

use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    abort(401);
}

$input = Validator::validate(request()->post(), [
    'game' => 'required_without:achievement|integer|exists:mysql_legacy.GameData,ID',
    'achievement' => 'required_without:game|integer|exists:mysql_legacy.Achievements,ID',
]);

if (!empty($input['achievement']) && resetSingleAchievement($user, (int) $input['achievement'])) {
    return response()->json(['message' => __('legacy.success.reset')]);
}

if (!empty($input['game']) && resetAchievements($user, (int) $input['game']) > 0) {
    return response()->json(['message' => __('legacy.success.reset')]);
}

abort(400);
