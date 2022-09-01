<?php

use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    abort(401);
}

$input = Validator::validate(request()->post(), [
    'game' => 'required_unless:achievement|integer|exists:mysql_legacy.GameData,ID',
    'achievement' => 'required_unless:game|integer|exists:mysql_legacy.GameData,ID',
]);

$gameId = (int) $input['game'];
$achievementId = (int) $input['achievement'];

if (!empty($achievementId) && resetSingleAchievement($user, $achievementId)) {
    return response()->json(['message' => __('legacy.success.reset')]);
}

if (!empty($gameId) && resetAchievements($user, $gameId) > 0) {
    return response()->json(['message' => __('legacy.success.reset')]);
}

abort(400);
