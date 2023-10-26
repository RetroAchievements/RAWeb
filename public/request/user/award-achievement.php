<?php

use App\Platform\Actions\UnlockPlayerAchievement;
use App\Platform\Models\Achievement;
use App\Site\Enums\Permissions;
use App\Site\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

$user = request()->user();
if ($user === null) {
    abort(401);
}

if ($user->Permissions < Permissions::Moderator) {
    abort(403);
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'user' => 'required|string|exists:UserAccounts,User',
    'achievement' => 'required|integer|exists:Achievements,ID',
    'hardcore' => 'required|integer|min:0|max:1'
]);

$player = User::firstWhere('User', $input['user']);

$achievementId = $input['achievement'];
$awardHardcore = (bool) $input['hardcore'];

$awardResponse = unlockAchievement($player, $achievementId, $awardHardcore);
if (array_key_exists('Error', $awardResponse)) {
   return response()->json(['error' => $awardResponse['Error']]);
}

$action = app()->make(UnlockPlayerAchievement::class);

$action->execute(
    $player,
    Achievement::findOrFail($achievementId),
    $awardHardcore,
    unlockedBy: $user,
);

return response()->json(['message' => __('legacy.success.achievement_unlocked')]);
