<?php

use App\Community\Enums\ArticleType;
use App\Platform\Enums\AchievementType;
use App\Platform\Models\Achievement;
use App\Site\Enums\Permissions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    abort(401);
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'achievements' => 'required',
    'type' => ['nullable', 'string', Rule::in(AchievementType::cases())],
]);

$achievementIds = $input['achievements'];
$value = $input['type'];

// Check for authorship on achievements if a Jr. is editing the type
$isSoleDeveloper = Achievement::find($achievementIds)->pluck('Author')->unique()->toArray() === [$user];
if ($permissions === Permissions::JuniorDeveloper && !$isSoleDeveloper) {
    abort(403);
}

if (updateAchievementType($achievementIds, $value)) {
    $commentText = '';
    if ($value === AchievementType::Progression) {
        $commentText = "set this achievement's type to Progression";
    }
    if ($value === AchievementType::WinCondition) {
        $commentText = "set this achievement's type to Win Condition";
    }
    if (!$value) {
        $commentText = "removed this achievement's type";
    }
    addArticleComment("Server", ArticleType::Achievement, $achievementIds, "$user $commentText.", $user);

    return response()->json(['message' => __('legacy.success.ok')]);
}

abort(400);
