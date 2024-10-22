<?php

use App\Models\Achievement;
use App\Models\Role;
use App\Platform\Actions\UpdateEventAchievement;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

$user = Auth::user();
if (!$user || !$user->hasRole(Role::EVENT_MANAGER)) {
    abort(403);
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'achievement' => 'required|integer|exists:Achievements,ID',
    'sourceAchievementId' => 'nullable|integer|exists:Achievements,ID',
    'activeFromDate' => 'nullable|date',
    'activeUntilDate' => 'nullable|date',
]);

$achievementId = (int) $input['achievement'];

$achievement = Achievement::find($achievementId);
if (!$achievement) {
    abort(404);
}

$sourceAchievementId = (int) $input['sourceAchievementId'];
$sourceAchievement = Achievement::find($sourceAchievementId);

(new UpdateEventAchievement())->execute($achievement, $sourceAchievement,
    $input['activeFromDate'] ?? null,
    $input['activeUntilDate'] ?? null
);

return response()->json(['message' => __('legacy.success.ok')]);
