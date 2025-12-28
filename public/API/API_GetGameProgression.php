<?php

/*
 *  API_GetGameProgression - returns information about the average time to unlock achievements in a game
 *    i : game id
 *    h : 1=prefer players with more hardcore unlocks than non-hardcore unlocks
 *
 *  int        ID                                unique identifier of the game
 *  string     Title                             name of the game
 *  int        ConsoleID                         unique identifier of the console associated to the game
 *  string     ConsoleName                       name of the console associated to the game
 *  string     ImageIcon                         site-relative path to the game's icon image
 *  int        NumDistinctPlayers                number of unique players who have earned achievements for the game
 *  int        TimesUsedInBeatMedian             number of beats analyzed for the MedianTimeToBeat metric
 *  int        TimesUsedInHardcoreBeatMedian     number of hardcore beats analyzed for the MedianTimeToBeatHardcore metric
 *  int?       MedianTimeToBeat                  median number of seconds required to beat the game
 *  int?       MedianTimeToBeatHardcore          median number of seconds required to beat the game in hardcore
 *  int        TimesUsedInCompletionMedian       number of completions analyzed for the MedianTimeToComplete metric
 *  int        TimesUsedInMasteryMedian          number of masteries analyzed for the MedianTimeToMaster metric
 *  int?       MedianTimeToComplete              median number of seconds required to complete the game
 *  int?       MedianTimeToMaster                median number of seconds required to master the game
 *  int        NumAchievements                   count of core achievements associated to the game
 *  array      Achievements
 *   int        ID                               unique identifier of the achievement
 *   string     Title                            title of the achievement
 *   string     Description                      description of the achievement
 *   int        Points                           number of points the achievement is worth
 *   int        TrueRatio                        number of RetroPoints ("white points") the achievement is worth
 *   string?    Type                             type of the achievement (progression/win_condition/missable/null)
 *   string     BadgeName                        unique identifier of the badge image for the achievement
 *   int        NumAwarded                       number of times the achievement has been awarded
 *   int        NumAwardedHardcore               number of times the achievement has been awarded in hardcore
 *   int        TimesUsedInUnlockMedian          number of unlocks analyzed for the MedianTimeToUnlock metric
 *   int        TimesUsedInHardcoreUnlockMedian  number of unlocks analyzed for the MedianTimeToUnlockHardcore metric
 *   int        MedianTimeToUnlock               median number of seconds required to unlock this achievement from starting to play the game
 *   int        MedianTimeToUnlockHardcore       median number of seconds required to unlock this achievement in hardcore from starting to play the game
 */

use App\Models\Achievement;
use App\Models\Game;
use App\Models\PlayerAchievement;
use App\Models\PlayerGame;
use App\Models\PlayerProgressReset;
use App\Models\PlayerSession;
use App\Platform\Enums\PlayerProgressResetType;

$gameId = (int) request()->query('i');
$preferHardcore = (int) request()->query('h');

$game = Game::with('system')->find($gameId);
if (!$game) {
    return response()->json([], 404);
}

// ===== basic game information =====
$coreSet = $game->gameAchievementSets()->core()->first()?->achievementSet;
$achievementsFirstPublishedAt = $coreSet?->achievements_first_published_at;

$response = [
    'ID' => $game->ID,
    'Title' => $game->Title,
    'ConsoleID' => $game->system->ID,
    'ConsoleName' => $game->system->Name,
    'ImageIcon' => $game->ImageIcon,
    'NumDistinctPlayers' => $game->players_total,
    'TimesUsedInBeatMedian' => $game->times_beaten,
    'TimesUsedInHardcoreBeatMedian' => $game->times_beaten_hardcore,
    'MedianTimeToBeat' => $game->median_time_to_beat,
    'MedianTimeToBeatHardcore' => $game->median_time_to_beat_hardcore,
    'TimesUsedInCompletionMedian' => $coreSet?->times_completed ?? 0,
    'TimesUsedInMasteryMedian' => $coreSet?->times_completed_hardcore ?? 0,
    'MedianTimeToComplete' => $coreSet?->median_time_to_complete,
    'MedianTimeToMaster' => $coreSet?->median_time_to_complete_hardcore,
    'NumAchievements' => $game->achievements_published,
    'Achievements' => [],
];

$achievements = $game->achievements()->promoted()->get();
$achievementIds = $achievements->pluck('id');
$unlock_times = [];
$unlock_hardcore_times = [];
foreach ($achievementIds as $achievementId) {
    $unlock_times[$achievementId] = [];
    $unlock_hardcore_times[$achievementId] = [];
}

// ===== process recent players in the set =====
// if the set has more than 500 players, only look at players who have earned at least half the achievements.
// if the set has between 200 and 500 players, only look at players who have earned at least 25% of the achievements.
// otherwise, only look at players who have earned at least two achievements.
$unlockThresholds = [
    (int) floor($game->achievements_published / 2),
    (int) floor($game->achievements_published / 4),
    2,
];

$recentPlayerIds = [];

$unlockThreshold = ($game->players_total > 500) ? 0 : (($game->players_total > 200) ? 1 : 2);
while (true) {
    $playerIds = PlayerGame::query()
        ->where('game_id', $game->ID)
        ->where($preferHardcore ? 'achievements_unlocked_hardcore' : 'achievements_unlocked', '>=', $unlockThresholds[$unlockThreshold])
        ->orderByDesc('last_unlock_at')
        ->limit(100)
        ->pluck('user_id')
        ->toArray();

    // previous thresholds contain more interesting data, merge in new results.
    $recentPlayerIds = array_unique(array_merge($recentPlayerIds, $playerIds));

    if ($unlockThreshold === 2 || count($recentPlayerIds) > 60) {
        break;
    }

    // threshold eliminated too many results. try again at next threshold.
    $unlockThreshold++;
}

$resets = PlayerProgressReset::query()
    ->where('type', PlayerProgressResetType::Game)
    ->where('type_id', $game->ID)
    ->whereIn('user_id', $recentPlayerIds)
    ->pluck('created_at', 'user_id');

// ===== build unlock lists grouped by user (in reverse order so we can use array_pop) =====
$unlocks = [];
$allUnlocks = PlayerAchievement::query()
    ->whereIn('user_id', $recentPlayerIds)
    ->whereIn('achievement_id', $achievementIds)
    ->whereNull('unlocker_id')
    ->orderByRaw('COALESCE(unlocked_hardcore_at, unlocked_at) DESC');
foreach ($allUnlocks->get() as $unlock) {
    if (!array_key_exists($unlock->user_id, $unlocks)) {
        $unlocks[$unlock->user_id] = [];

        // if the first published at timestamp hasn't been calculated yet, use the first unlock we found
        $achievementsFirstPublishedAt ??= $unlock->unlocked_at->clone()->subMinutes(10);
    }

    $unlocks[$unlock->user_id][] = [
        'achievement_id' => $unlock->achievement_id,
        'unlocked_at' => $unlock->unlocked_at,
        'unlocked_hardcore_at' => $unlock->unlocked_hardcore_at,
    ];
}

// ===== process all sessions for users in above list =====
$totalSessionTime = [];
$allSessions = PlayerSession::query()
    ->where('game_id', $game->id)
    ->whereIn('user_id', array_keys($unlocks))
    ->when($achievementsFirstPublishedAt, fn ($q) => $q->where('rich_presence_updated_at', '>', $achievementsFirstPublishedAt))
    ->select(['user_id', 'created_at', 'duration', 'rich_presence_updated_at'])
    ->orderBy('created_at');
foreach ($allSessions->get() as $session) {
    $userUnlocks = $unlocks[$session->user_id];
    if (empty($userUnlocks)) {
        // already processed all achievements for this user
        continue;
    }

    $achievementSessionStart = $resets[$session->user_id] ?? $achievementsFirstPublishedAt;
    $sessionStart = $achievementSessionStart ? max($achievementSessionStart, $session->created_at) : $session->created_at;
    $sessionEnd = max($session->rich_presence_updated_at, $session->created_at->addMinutes($session->duration));

    if ($sessionEnd < $achievementSessionStart) {
        // ignore sessions prior to the achievements being published or the player's last full reset
        continue;
    }

    // get time from previous sessions for user
    $elapsed = $totalSessionTime[$session->user_id] ?? 0;
    do {
        // unlocks are sorted by date desc, so the last element will be the earliest
        $unlock = end($userUnlocks);

        if ($unlock['unlocked_hardcore_at']) {
            if (!$unlock['unlocked_hardcore_at']->between($sessionStart, $sessionEnd)) {
                break;
            }

            $unlock_hardcore_times[$unlock['achievement_id']][] = $elapsed +
                $unlock['unlocked_hardcore_at']->diffInSeconds($sessionStart, true);

            if ($unlock['unlocked_at']->between($sessionStart, $sessionEnd)) {
                $unlock_times[$unlock['achievement_id']][] = $elapsed +
                    $unlock['unlocked_at']->diffInSeconds($sessionStart, true);
            }
        } elseif ($unlock['unlocked_at']) {
            if (!$unlock['unlocked_at']->between($sessionStart, $sessionEnd)) {
                break;
            }

            $unlock_times[$unlock['achievement_id']][] = $elapsed +
                $unlock['unlocked_at']->diffInSeconds($sessionStart, true);
        }

        // remove processed element so we don't try to process it again.
        // this also allows us to avoid processing the user in the future once
        // all of their achievements have been processed.
        array_pop($userUnlocks);
    } while (!empty($userUnlocks));

    // update state for next session
    $unlocks[$session->user_id] = $userUnlocks;
    $totalSessionTime[$session->user_id] = $elapsed + $sessionEnd->diffInSeconds($sessionStart, true);
}

// ===== summarize achievement metrics =====
$get_median = function (array $a): int {
    $length = count($a);
    if ($length === 0) {
        return 0;
    }

    $values = array_values($a);
    sort($values);

    $index = (int) floor($length / 2);
    if (($length % 2) == 1) {
        return $values[$index];
    }

    return (int) round(($values[$index - 1] + $values[$index]) / 2);
};

foreach ($achievements as $achievement) {
    $response['Achievements'][] = [
        'ID' => $achievement->id,
        'Title' => $achievement->title,
        'Description' => $achievement->description,
        'Points' => $achievement->points,
        'TrueRatio' => $achievement->points_weighted,
        'Type' => $achievement->type,
        'BadgeName' => $achievement->image_name,
        'NumAwarded' => $achievement->unlocks_total,
        'NumAwardedHardcore' => $achievement->unlocks_hardcore,
        'TimesUsedInUnlockMedian' => count($unlock_times[$achievement->id]),
        'TimesUsedInHardcoreUnlockMedian' => count($unlock_hardcore_times[$achievement->id]),
        'MedianTimeToUnlock' => $get_median($unlock_times[$achievement->id]),
        'MedianTimeToUnlockHardcore' => $get_median($unlock_hardcore_times[$achievement->id]),
    ];
}

usort($response['Achievements'], fn ($a, $b) => $a['MedianTimeToUnlockHardcore'] - $b['MedianTimeToUnlockHardcore']);

// ===== send response =====
return response()->json($response);
