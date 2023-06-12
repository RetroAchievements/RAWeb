<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use LegacyApp\Community\Enums\ArticleType;
use LegacyApp\Platform\Enums\AchievementType;
use LegacyApp\Site\Enums\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    abort(401);
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'achievements' => 'required',
    'flag' => ['required', 'integer', Rule::in(AchievementType::cases())],
]);

$achievementIds = $input['achievements'];
$value = (int) $input['flag'];

$achievement = GetAchievementData((int) (is_array($achievementIds) ? $achievementIds[0] : $achievementIds));
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
    addArticleComment("Server", ArticleType::Achievement, $achievementIds, "$user $commentText.", $user);
    expireGameTopAchievers($achievement['GameID']);

    return response()->json(['message' => __('legacy.success.ok')]);
}

abort(400);
