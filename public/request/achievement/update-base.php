<?php

use App\Enums\Permissions;
use App\Models\Achievement;
use App\Platform\Enums\AchievementType;
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
if ($permissions === Permissions::JuniorDeveloper && $user !== $achievement->developer?->User) {
    abort(403);
}

$achievementId = $achievement->id;

if (UploadNewAchievement(
    authorUsername: $user,
    gameID: $achievement->GameID,
    title: $input['title'],
    desc: $input['description'],
    points: $input['points'],
    type: $input['type'] ?? null,
    mem: $achievement->MemAddr,
    flag: $achievement->Flags,
    idInOut: $achievementId,
    badge: $achievement->BadgeName,
    errorOut: $errorOut
)) {
    return response()->json(['message' => __('legacy.success.achievement_update')]);
}

abort(400);
