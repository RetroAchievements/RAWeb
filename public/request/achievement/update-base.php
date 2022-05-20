<?php

use App\Platform\Models\Achievement;
use App\Site\Enums\Permissions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'achievement' => 'required|integer|exists:Achievements,ID',
    'title' => 'required|string|max:64',
    'description' => 'required|max:255',
    'points' => 'required|integer',
]);

$achievement = Achievement::find($input['achievement']);

// Only allow jr. devs to update base data if they are the author
if ($permissions == Permissions::JuniorDeveloper && $user != $achievement['Author']) {
    abort(403);
}

$achievementId = $achievement['ID'];

if (UploadNewAchievement(
    author: $user,
    gameID: $achievement['GameID'],
    title: $input['title'],
    desc: $input['description'],
    progress: $achievement['Progress'],
    progressMax: $achievement['ProgressMax'],
    progressFmt: $achievement['ProgressFormat'],
    points: $input['points'],
    mem: $achievement['MemAddr'],
    type: $achievement['Flags'],
    idInOut: $achievementId,
    badge: $achievement['BadgeName'],
    errorOut: $errorOut
)) {
    return response()->json(['message' => __('legacy.success.achievement_update')]);
}

abort(400);
