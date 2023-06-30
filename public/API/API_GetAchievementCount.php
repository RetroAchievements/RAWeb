<?php

use App\Platform\Models\Achievement;

/*
 *  API_GetAchievementCount - returns the achievements associated to a game
 *    i : game id
 *
 *  int        GameID                     unique identifier of the game
 *  array      AchievementIDs
 *   int        [value]                   unique identifier of an achievement associated to the game
 */

$gameID = (int) request()->query('i');

return response()->json([
    'GameID' => $gameID,
    'AchievementIDs' => Achievement::where('GameID', $gameID)
        ->published()
        ->orderBy('ID')
        ->pluck('ID'),
]);
