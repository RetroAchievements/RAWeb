<?php

use App\Community\Enums\CommentableType;
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
    'flag' => ['required', 'integer', Rule::in([Achievement::FLAG_PROMOTED, Achievement::FLAG_UNPROMOTED])],
]);

$achievementIds = $input['achievements'];
$isPromoted = Achievement::isPromotedFromLegacyFlags((int) $input['flag']);

$achievement = GetAchievementData((int) (is_array($achievementIds) ? $achievementIds[0] : $achievementIds));
if ($isPromoted && !isValidConsoleId($achievement['ConsoleID'])) {
    abort(400, 'Invalid console');
}

updateAchievementPromotedStatus($achievementIds, $isPromoted);

$userModel = User::whereName($user)->first();

$commentText = '';
if ($isPromoted) {
    $commentText = 'promoted this achievement to the Core set';
} else {
    $commentText = 'demoted this achievement to Unofficial';
}
addArticleComment("Server", CommentableType::Achievement, $achievementIds, "{$userModel->display_name} $commentText.", $userModel->display_name);
expireGameTopAchievers($achievement['GameID']);

return response()->json(['message' => __('legacy.success.ok')]);
