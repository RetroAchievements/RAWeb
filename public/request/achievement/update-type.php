<?php

use App\Community\Enums\ArticleType;
use App\Platform\Enums\AchievementType;
use App\Site\Enums\Permissions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    abort(401);
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'achievements' => 'required',
    'type' => ['required', 'string', Rule::in(AchievementType::cases())],
]);

$achievementIds = $input['achievements'];
$value = $input['type'];

if (updateAchievementType($achievementIds, $value)) {
    $commentText = '';
    if ($value === AchievementType::Progression) {
        $commentText = 'set this achievement to Progression';
    }
    if ($value === AchievementType::WinCondition) {
        $commentText = 'set this achievement to Win Condition';
    }
    addArticleComment("Server", ArticleType::Achievement, $achievementIds, "$user $commentText.", $user);

    return response()->json(['message' => __('legacy.success.ok')]);
}

abort(400);
