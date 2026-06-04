<?php

/*
 *  API_GetGameProgression - returns information about the average time to unlock achievements in a game
 *    i : game id
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

$gameId = (int) request()->query('i');

$game = Game::with('system')->find($gameId);
if (!$game) {
    return response()->json([], 404);
}

// ===== basic game information =====
$coreSet = $game->gameAchievementSets()->core()->first()?->achievementSet;

$response = [
    'ID' => $game->id,
    'Title' => $game->title,
    'ConsoleID' => $game->system->id,
    'ConsoleName' => $game->system->name,
    'ImageIcon' => $game->image_icon_asset_path,
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
        'TimesUsedInUnlockMedian' => $achievement->median_time_to_unlock_samples,
        'TimesUsedInHardcoreUnlockMedian' => $achievement->median_time_to_unlock_hardcore_samples,
        'MedianTimeToUnlock' => $achievement->median_time_to_unlock,
        'MedianTimeToUnlockHardcore' => $achievement->median_time_to_unlock_hardcore,
    ];
}

usort($response['Achievements'], fn ($a, $b) => $a['MedianTimeToUnlockHardcore'] - $b['MedianTimeToUnlockHardcore']);

// ===== send response =====
return response()->json($response);
