<?php

/*
 *  API_GetLeaderboardCount - returns the number of leaderboards associated with a game and their details
 *    g : game id
 *
 *  int        GameID                     unique identifier of the game
 *  int        LeaderboardCount           number of leaderboards associated with the game
 *  array      Leaderboards               details of each leaderboard
 */

$gameID = (int) request()->query('g');
$leaderboardData = [];

$leaderboardCount = getLeaderboardsForGame($gameID, $leaderboardData, null, true);

return response()->json([
    'GameID' => $gameID,
    'LeaderboardCount' => $leaderboardCount,
    'Leaderboards' => array_values($leaderboardData)
]);
