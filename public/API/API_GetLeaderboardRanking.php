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

$lowerIsBetter = (bool) Leaderboard::find($lbID)->LowerIsBetter;

$rankingData = GetLeaderboardRankingJSON($userName, $lbID, $lowerIsBetter);

if (empty($rankingData)) {
    $totalEntries = Leaderboard::find($lbID)->entries()->count();
    return response()->json([
        'User' => $userName,
        'LeaderboardID' => $lbID,
        'Rank' => $totalEntries,
        'NumEntries' => $totalEntries
    ]);
}

return response()->json([
    'User' => $userName,
    'LeaderboardID' => $lbID,
    'Rank' => $rankingData['Rank'],
    'NumEntries' => $rankingData['NumEntries']
]);
