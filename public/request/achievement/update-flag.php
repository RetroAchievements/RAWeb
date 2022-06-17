<?php

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RA\AchievementType;
use RA\ArticleType;
use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    abort(401);
}

$input = Validator::validate(request()->post(), [
    'achievements' => 'required',
    'flag' => ['required', 'integer', Rule::in(AchievementType::VALID)],
]);

$achievementIds = $input['achievements'];
$value = (int) $input['flag'];

$achievement = GetAchievementMetadataJSON((int) (is_array($achievementIds) ? $achievementIds[0] : $achievementIds));
if ($value === AchievementType::OfficialCore && !isValidConsoleId($achievement['ConsoleID'])) {
    abort(400, 'Invalid console');
}

if (updateAchievementFlags($achievementIds, $value)) {
    $commentText = '';
    if ($value == AchievementType::OfficialCore) {
        $commentText = 'promoted this achievement to the Core set';
    }
    if ($value == AchievementType::Unofficial) {
        $commentText = 'demoted this achievement to Unofficial';
    }
    addArticleComment("Server", ArticleType::Achievement, $achievementIds, "\"$user\" $commentText.", $user);

    return response()->json(['message' => __('legacy.success.ok')]);
}

abort(400);
