<?php

use App\Community\Enums\ArticleType;
use App\Enums\Permissions;
use App\Models\Achievement;
use App\Models\Game;
use App\Platform\Enums\AchievementType;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

if (!authenticateFromCookie($user, $permissions, Permissions::JuniorDeveloper)) {
    abort(401);
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'achievements' => 'required',
    'type' => ['nullable', 'string', Rule::in(AchievementType::cases())],
]);

$achievementIds = $input['achievements'];
$value = $input['type'];

$foundAchievements = Achievement::find($achievementIds);

// Don't allow adding beaten types to subsets or test kits.
$game = Game::find($foundAchievements->get(0)->GameID);
if (
    $game
    && !$game->getCanHaveBeatenTypes()
    && ($value === AchievementType::Progression || $value === AchievementType::WinCondition)
) {
    abort(400);
}

// Check for authorship on achievements if a Jr. is editing the type
$isSoleDeveloper = $foundAchievements->pluck('user_id')->unique()->toArray() === [$user->id];
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
    if ($value === AchievementType::Missable) {
        $commentText = "set this achievement's type to Missable";
    }
    if (!$value) {
        $commentText = "removed this achievement's type";
    }
    addArticleComment("Server", ArticleType::Achievement, $achievementIds, "{$user->display_name} $commentText.", $user->username);

    return response()->json(['message' => __('legacy.success.ok')]);
}

abort(400);
