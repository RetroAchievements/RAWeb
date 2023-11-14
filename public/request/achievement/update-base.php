<?php

use App\Platform\Enums\AchievementType;
use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Site\Enums\Permissions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'achievement' => 'required|integer|exists:Achievements,ID',
    'title' => 'required|string|max:64',
    'description' => 'required|max:255',
    'points' => 'required|integer',
    'type' => ['nullable', 'string', Rule::in(AchievementType::cases())],
]);

$achievement = Achievement::find($input['achievement']);

// Only allow jr. devs to update base data if they are the author
if ($permissions == Permissions::JuniorDeveloper && $user != $achievement['Author']) {
    abort(403);
}

// Don't allow adding types to subsets or test kits.
$game = Game::find((int) $achievement['GameID']);
if ($game) {
    $canHaveTypes = mb_strpos($game->Title, "[Subset") === false && mb_strpos($game->Title, "~Test Kit~") === false;
    if (!$canHaveTypes && $input['type']) {
        abort(400);
    }
}

$achievementId = $achievement['ID'];

if (UploadNewAchievement(
    author: $user,
    gameID: $achievement['GameID'],
    title: $input['title'],
    desc: $input['description'],
    points: $input['points'],
    type: $input['type'],
    mem: $achievement['MemAddr'],
    flag: $achievement['Flags'],
    idInOut: $achievementId,
    badge: $achievement['BadgeName'],
    errorOut: $errorOut
)) {
    return response()->json(['message' => __('legacy.success.achievement_update')]);
}

abort(400);
