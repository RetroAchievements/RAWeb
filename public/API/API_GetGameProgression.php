<?php

/*
 *  API_GetGameProgression - returns information about the average time to unlock achievements in a game
 *    i : game id
 *    h : 1=ignore users with no hardcore unlocks
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

$achievements = $game->achievements()->published()->get();
$achievementIds = $achievements->pluck('ID');
$unlock_times = [];
$unlock_hardcore_times = [];
foreach ($achievementIds as $achievementId) {
    $unlock_times[$achievementId] = [];
    $unlock_hardcore_times[$achievementId] = [];
}

// ===== process the 100 most recent players to earn at least half of the achievements in the set =====
$recentPlayerIds = PlayerGame::query()
    ->where('game_id', $game->ID)
    ->where($preferHardcore ? 'achievements_unlocked_hardcore' : 'achievements_unlocked', '>=', $game->achievements_published / 2)
    ->orderByDesc('last_unlock_at')
    ->limit(100)
    ->pluck('user_id');

$resets = PlayerProgressReset::query()
    ->where('type', PlayerProgressResetType::Game)
    ->where('type_id', $game->ID)
    ->whereIn('user_id', $recentPlayerIds)
    ->pluck('created_at', 'user_id');

foreach ($recentPlayerIds as $playerId) {
    $unlocks = PlayerAchievement::query()
        ->where('user_id', $playerId)
        ->whereIn('achievement_id', $achievementIds)
        ->whereNull('unlocker_id')
        ->get();
    if ($unlocks->count() === 0) {
        continue;
    }

    $achievementSessionStart = $resets[$playerId] ?? $coreSet?->achievements_first_published_at;

    $sessionQuery = PlayerSession::query()
        ->where('user_id', $playerId)
        ->where('game_id', $game->ID)
        ->when($achievementSessionStart, fn ($q) => $q->where('rich_presence_updated_at', '>', $achievementSessionStart))
        ->select(['created_at', 'duration', 'rich_presence_updated_at']);

    $elapsed = 0;
    foreach ($sessionQuery->orderBy('rich_presence_updated_at')->get() as $session) {
        $sessionStart = $achievementSessionStart ? max($achievementSessionStart, $session->created_at) : $session->created_at;
        $sessionEnd = max($session->rich_presence_updated_at, $session->created_at->addMinutes($session->duration));

        foreach ($unlocks as $unlock) {
            if ($unlock->unlocked_at && $unlock->unlocked_at->between($sessionStart, $sessionEnd)) {
                $unlock_times[$unlock->achievement_id][] =
                    $unlock->unlocked_at->diffInSeconds($sessionStart, true) + $elapsed;
            }
            if ($unlock->unlocked_hardcore_at && $unlock->unlocked_hardcore_at->between($sessionStart, $sessionEnd)) {
                $unlock_hardcore_times[$unlock->achievement_id][] =
                    $unlock->unlocked_hardcore_at->diffInSeconds($sessionStart, true) + $elapsed;
            }
        }

        $elapsed += $sessionEnd->diffInSeconds($sessionStart, true);
    }
}

// ===== summarize achievement metrics =====
$get_median = function (array $a): int {
    $length = count($a);
    if ($length === 0) {
        return 0;
    }

    $values = array_values($a);
    sort($values);

    $index = floor($length / 2);
    if (($length % 2) == 1) {
        return $values[$index];
    }

    return (int) round(($values[$index - 1] + $values[$index]) / 2);
};

foreach ($achievements as $achievement) {
    $response['Achievements'][] = [
        'ID' => $achievement->ID,
        'Title' => $achievement->Title,
        'Description' => $achievement->Description,
        'Points' => $achievement->Points,
        'TrueRatio' => $achievement->TrueRatio,
        'Type' => $achievement->type,
        'BadgeName' => $achievement->BadgeName,
        'NumAwarded' => $achievement->unlocks_total,
        'NumAwardedHardcore' => $achievement->unlocks_hardcore_total,
        'TimesUsedInUnlockMedian' => count($unlock_times[$achievement->ID]),
        'TimesUsedInHardcoreUnlockMedian' => count($unlock_hardcore_times[$achievement->ID]),
        'MedianTimeToUnlock' => $get_median($unlock_times[$achievement->ID]),
        'MedianTimeToUnlockHardcore' => $get_median($unlock_hardcore_times[$achievement->ID]),
    ];
}

usort($response['Achievements'], fn ($a, $b) => $a['MedianTimeToUnlockHardcore'] - $b['MedianTimeToUnlockHardcore']);

// ===== send response =====
return response()->json($response);
