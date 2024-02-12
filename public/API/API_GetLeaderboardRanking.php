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

$totalEntries = 0;
$lastRank = 1;
getLeaderboardRanking($userName, $lbID, $userRank, $totalEntries);

if ($lowerIsBetter) {
    $lastRank = $totalEntries;
} else {
    $lastRank = 1;
}

$rankingData = GetLeaderboardRankingJSON($userName, $lbID, $lowerIsBetter);

if (empty($rankingData)) {
    $rankingData = [
        'Rank' => $lastRank,
        'NumEntries' => $totalEntries
    ];
}

return response()->json(array_map('intval', [
    'Rank' => $rankingData['Rank'] ?? 0,
    'NumEntries' => $rankingData['NumEntries'] ?? 0
]));
