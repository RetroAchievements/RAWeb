<?php

use App\Models\User;

/*
 *  API_GetUserGameRankAndScore - gets user's High Scores entry for a game
 *    g : game id
 *    u : username
 *
 *  array
 *   object     [value]
 *    string     User             name of user
 *    string     TotalScore       total number of points earned by the user for the game
 *    datetime   LastAward        when the last achievement was unlocked for the user
 *    string?    UserRank         position of user on the game's High Scores list
 */

$gameId = (int) request()->query('g');

$user = User::whereName(request()->query('u'))->first();
if (!$user) {
    return response()->json([]);
}

return response()->json(getGameRankAndScore($gameId, $user));
