<?php

use App\Enums\Permissions;
use App\Models\PlayerAchievement;
use App\Models\User;
use App\Platform\Jobs\UnlockPlayerAchievementJob;
use Illuminate\Support\Carbon;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Moderator)) {
    abort(401);
}

$action = request()->input('action');
$message = null;

if ($action === 'manual-unlock') {
    $awardAchievementID = requestInputSanitized('a');
    $awardAchievementUser = requestInputSanitized('u');
    $awardAchHardcore = requestInputSanitized('h', 0, 'integer');

    if (isset($awardAchievementID) && isset($awardAchievementUser)) {
        $usersToAward = preg_split('/\W+/', $awardAchievementUser);
        foreach ($usersToAward as $nextUser) {
            $player = User::whereName($nextUser)->first();
            if (!$player) {
                continue;
            }
            $ids = separateList($awardAchievementID);
            foreach ($ids as $nextID) {
                dispatch(
                    new UnlockPlayerAchievementJob(
                        $player->id,
                        $nextID,
                        (bool) $awardAchHardcore,
                        unlockedByUserId: request()->user()->id
                    )
                );
            }
        }

        return back()->with('success', __('legacy.success.ok'));
    }

    return back()->withErrors(__('legacy.error.error'));
}

if ($action === 'copy-unlocks') {
    $fromAchievementIds = separateList(requestInputSanitized('s'));
    $fromAchievementCount = count($fromAchievementIds);
    $toAchievementIds = separateList(requestInputSanitized('a'));

    // determine which players have earned all of the required achievements
    $existing = PlayerAchievement::whereIn('achievement_id', $fromAchievementIds)
        ->select([
            'user_id',
            DB::raw('count(unlocked_at) AS softcore_count'),
            DB::raw('count(unlocked_hardcore_at) AS hardcore_count'),
            DB::raw('max(unlocked_at) AS unlocked_softcore_at'),
            DB::raw('max(unlocked_hardcore_at) AS unlocked_hardcore_at'),
        ])
        ->groupBy('user_id')
        ->having('softcore_count', '=', $fromAchievementCount)
        ->get();

    // award the target achievements, copying the unlock times and hardcore state
    $unlockerId = request()->user()->id;
    foreach ($existing as $playerAchievement) {
        $hardcore = ($playerAchievement->hardcore_count == $fromAchievementCount);
        $timestamp = Carbon::parse($hardcore ? $playerAchievement->unlocked_hardcore_at : $playerAchievement->unlocked_softcore_at);
        foreach ($toAchievementIds as $toAchievementId) {
            dispatch(
                new UnlockPlayerAchievementJob(
                    $playerAchievement->user_id,
                    (int) $toAchievementId,
                    hardcore: $hardcore,
                    timestamp: $timestamp,
                    unlockedByUserId: $unlockerId
                )
            );
        }
    }

    return back()->with('success', __('legacy.success.ok'));
}
