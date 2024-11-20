<?php

use App\Community\Enums\ArticleType;
use App\Enums\Permissions;
use App\Platform\Enums\AchievementFlag;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    abort(401);
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'achievements' => 'required',
    'flag' => ['required', 'integer', Rule::in(array_map(fn ($case) => $case->value, AchievementFlag::cases()))],
]);

$achievementIds = $input['achievements'];
$flag = AchievementFlag::from((int) $input['flag']);

$achievement = GetAchievementData((int) (is_array($achievementIds) ? $achievementIds[0] : $achievementIds));
if ($flag === AchievementFlag::OfficialCore && !isValidConsoleId($achievement['ConsoleID'])) {
    abort(400, 'Invalid console');
}

updateAchievementFlag($achievementIds, $flag);

$commentText = '';
if ($flag === AchievementFlag::OfficialCore) {
    $commentText = 'promoted this achievement to the Core set';
}
if ($flag === AchievementFlag::Unofficial) {
    $commentText = 'demoted this achievement to Unofficial';
}
addArticleComment("Server", ArticleType::Achievement, $achievementIds, "$user $commentText.", $user);
expireGameTopAchievers($achievement['GameID']);

return response()->json(['message' => __('legacy.success.ok')]);
