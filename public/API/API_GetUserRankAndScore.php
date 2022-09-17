<?php

/*
 *  API_GetUserRankAndScore
 *    u : username
 *
 *  int        Score           number of hardcore points the user has
 *  int        SoftcoreScore   number of softcore points the user has
 *  int?       Rank            user's site rank
 *  string     TotalRanked     total number of ranked users
 */

$user = request()->query('u');

$points = 0;
$softcorePoints = 0;
if (getPlayerPoints($user, $playerPoints)) {
    $points = $playerPoints['RAPoints'];
    $softcorePoints = $playerPoints['RASoftcorePoints'];
}

return response()->json([
    'Score' => $points,
    'SoftcoreScore' => $softcorePoints,
    'Rank' => getUserRank($user),
    'TotalRanked' => countRankedUsers(),
]);
