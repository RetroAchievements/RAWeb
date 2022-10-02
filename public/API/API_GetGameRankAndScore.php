<?php

/*
 *  API_GetGameRankAndScore - returns Latest Masters or High Scores entries for a game
 *    g : game id
 *    t : type. 1=Latest Masters, 0=High Scores (default: 0)
 *
 *  array
 *   object     [value]
 *    string     User                name of user
 *    string     TotalScore          number of points earned by the user for the game (includes hardcore bonus)
 *    datetime   LastAward           when the user's latest achievement for the game was unlocked
 */

$gameId = (int) request()->query('g');
if ($gameId <= 0) {
    return response()->json(['success' => false]);
}

$username = request()->query('z');
$type = (int) request()->query('t');

$gameTopAchievers = getGameTopAchievers($gameId);

if ($type == 1) {
    return response()->json($gameTopAchievers['Masters']);
}

return response()->json($gameTopAchievers['HighScores']);
