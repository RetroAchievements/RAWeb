<?php

/*
 *  API_GetUserProgress - gets user's High Scores entry for a game
 *    i : CSV of game ids
 *    u : username
 *
 *  map
 *   string     [key]                       unique identifier of the game
 *    int        NumPossibleAchievements    number of core achievements for the game
 *    string     PossibleScore              maximum number of points that can be earned from the game
 *    int        NumAchieved                number of achievements earned by the user in softcore
 *    string     ScoreAchieved              number of points earned by the user in softcore
 *    int        NumAchievedHardcore        number of achievements earned by the user in hardcore
 *    string     ScoreAchievedHardcore      number of points earned by the user in hardcore
 */

$user = request()->query('u');
$gameCSV = request()->query('i', "");

$gameIDs = collect(explode(',', $gameCSV))
    ->map(fn ($id) => is_numeric($id) ? (int) $id : 0)
    ->filter(fn ($id) => $id > 0)
    ->toArray();

$data = getUserProgress($user, $gameIDs);

return response()->json($data['Awarded']);
