<?php

use App\Models\Achievement;
use App\Models\User;
use App\Platform\Actions\UnlockPlayerAchievementAction;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

$user = request()->user();
if ($user === null) {
    abort(401);
}

if (!$user->can('manuallyAward', App\Models\PlayerAchievement::class)) {
    abort(403);
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'user' => 'required|string|exists:users,display_name',
    'achievement' => 'required|integer|exists:achievements,id',
    'hardcore' => 'required|integer|min:0|max:1',
]);

$player = User::whereName($input['user'])->first();

$achievementId = $input['achievement'];
$awardHardcore = (bool) $input['hardcore'];

$action = app()->make(UnlockPlayerAchievementAction::class);

$action->execute(
    $player,
    Achievement::findOrFail($achievementId),
    $awardHardcore,
    unlockedBy: $user,
);

return response()->json(['message' => __('legacy.success.achievement_unlocked')]);
