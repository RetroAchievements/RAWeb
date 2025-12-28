<?php

use App\Community\Enums\ArticleType;
use App\Enums\Permissions;
use App\Models\Achievement;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    abort(401);
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'achievements' => 'required',
    'flag' => ['required', 'integer', Rule::in([Achievement::FLAG_PUBLISHED, Achievement::FLAG_UNPUBLISHED])],
]);

$achievementIds = $input['achievements'];
$isPublished = Achievement::isPublishedFromLegacyFlags((int) $input['flag']);

$achievement = GetAchievementData((int) (is_array($achievementIds) ? $achievementIds[0] : $achievementIds));
if ($isPublished && !isValidConsoleId($achievement['ConsoleID'])) {
    abort(400, 'Invalid console');
}

updateAchievementPublishedStatus($achievementIds, $isPublished);

$userModel = User::whereName($user)->first();

$commentText = '';
if ($isPublished) {
    $commentText = 'promoted this achievement to the Core set';
} else {
    $commentText = 'demoted this achievement to Unofficial';
}
addArticleComment("Server", ArticleType::Achievement, $achievementIds, "{$userModel->display_name} $commentText.", $userModel->display_name);
expireGameTopAchievers($achievement['GameID']);

return response()->json(['message' => __('legacy.success.ok')]);
