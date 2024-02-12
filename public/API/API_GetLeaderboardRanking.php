<?php

/*
 *  API_GetLeaderboardRanking
 *    u : username
 *    lbID : leaderboard id
 *    lowerIsBetter : boolean to indicate if a lower score is better
 *
 *  Returns:
 *  int        Rank                    rank of the user in the specified leaderboard
 */

$userName = request()->query('u');
$lbID = (int) request()->query('lbID');
$lowerIsBetter = (bool) request()->query('lowerIsBetter');

$rankingData = GetLeaderboardRankingJSON($userName, $lbID, $lowerIsBetter);

if (empty($rankingData)) {
    return response()->json([
        'User' => $userName,
        'LeaderboardID' => $lbID,
        'Rank' => null
    ]);
}

return response()->json([
    'User' => $userName,
    'LeaderboardID' => $lbID,
    'Rank' => $rankingData['Rank']
]);
