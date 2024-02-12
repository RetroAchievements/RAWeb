<?php

/*
 *  API_GetLeaderboardRanking
 *    u : username
 *    lbID : leaderboard id
 *
 *  int        Rank                    rank of the user in the specified leaderboard
 *  int        NumEntries              total number of entries in the leaderboard
 */

$userName = request()->query('u');
$lbID = (int) request()->query('lbID');

$lowerIsBetter = true;

$rankingData = GetLeaderboardRankingJSON($userName, $lbID, $lowerIsBetter);

if (empty($rankingData)) {
    return response()->json([
        'User' => $userName,
        'LeaderboardID' => $lbID
    ], 404);
}

return response()->json(array_map('intval', [
    'Rank' => $rankingData['Rank'] ?? 0,
    'NumEntries' => $rankingData['NumEntries'] ?? 0
]));
