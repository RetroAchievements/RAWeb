<?php

use App\Community\Enums\ArticleType;
use App\Enums\Permissions;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\PlayerAchievement;
use App\Models\PlayerGame;
use App\Models\User;
use App\Platform\Jobs\UnlockPlayerAchievementJob;
use App\Platform\Jobs\UpdateGameMetricsJob;
use App\Platform\Jobs\UpdatePlayerGameMetricsJob;
use Illuminate\Support\Carbon;

if (!authenticateFromCookie($user, $permissions, Permissions::Moderator)) {
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
            $player = User::firstWhere('User', $nextUser);
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

if ($action === 'migrate-achievement') {
    $achievementIds = explode(',', requestInputSanitized('a'));
    $gameId = requestInputSanitized('g');
    if (!Game::where('ID', $gameId)->exists()) {
        return back()->withErrors('Unknown game');
    }

    // determine which game(s) the achievements are coming from
    $oldGames = Achievement::whereIn('ID', $achievementIds)->select(['GameID'])->distinct()->pluck('GameID');

    // associate the achievements to the new game
    Achievement::whereIn('ID', $achievementIds)->update(['GameID' => $gameId]);

    // add an audit comment to the new game
    addArticleComment(
        'Server',
        ArticleType::GameModification,
        $gameId,
        "$user migrated " . Str::plural('achievement', count($achievementIds)) . ' ' .
            implode(',', $achievementIds) . ' from ' .
            Str::plural('game', count($oldGames)) . ' ' . $oldGames->implode(',') . '.'
    );

    // ensure player_game entries exist for the new game for all affected users
    foreach (PlayerAchievement::whereIn('achievement_id', $achievementIds)->select(['user_id'])->distinct()->pluck('user_id') as $userId) {
        if (!PlayerGame::where('game_id', $gameId)->where('user_id', $userId)->exists()) {
            $playerGame = new PlayerGame(['user_id' => $userId, 'game_id' => $gameId]);
            $playerGame->save();
            dispatch(new UpdatePlayerGameMetricsJob($userId, $gameId));
        }
    }

    // update the metrics on the new game and the old game(s)
    dispatch(new UpdateGameMetricsJob($gameId))->onQueue('game-metrics');
    foreach ($oldGames as $oldGameId) {
        dispatch(new UpdateGameMetricsJob($oldGameId))->onQueue('game-metrics');
    }

    return back()->with('success', __('legacy.success.ok'));
}

if ($action === 'aotw') {
    $aotwAchID = requestInputSanitized('a', 0, 'integer');
    $aotwForumID = requestInputSanitized('f', 0, 'integer');
    $aotwStartAt = requestInputSanitized('s', null, 'string');

    $query = "UPDATE StaticData SET
        Event_AOTW_AchievementID='$aotwAchID',
        Event_AOTW_ForumID='$aotwForumID',
        Event_AOTW_StartAt='$aotwStartAt'";

    $db = getMysqliConnection();
    $result = s_mysql_query($query);

    if ($result) {
        return back()->with('success', __('legacy.success.ok'));
    }

    return back()->withErrors(__('legacy.error.error'));
}
