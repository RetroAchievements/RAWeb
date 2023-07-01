<?php

/*
 *  API_GetGameRating - gets the overall rating of the game
 *    i : game id
 *
 *  string      GameID                 unique identifier of the game
 *  object      Ratings
 *   double      Game                  average rating of the game
 *   int         GameNumVotes          number of votes contributing to the game's rating
 *   double      Achievements          average rating of the game's achievements (deprecated)
 *   int         AchievementsNumVotes  number of votes contributing to the game's achievements rating (deprecated)
 */

use App\Community\Enums\RatingType;

$gameID = (int) request()->query('i');

$gameRating = getGameRating($gameID);

if (!isset($gameRating[RatingType::Game])) {
    $gameRating[RatingType::Game]['AverageRating'] = 0.0;
    $gameRating[RatingType::Game]['RatingCount'] = 0;
}
if (!isset($gameRating[RatingType::Achievement])) {
    $gameRating[RatingType::Achievement]['AverageRating'] = 0.0;
    $gameRating[RatingType::Achievement]['RatingCount'] = 0;
}

return response()->json([
    'GameID' => $gameID,
    'Ratings' => [
        'Game' => $gameRating[RatingType::Game]['AverageRating'],
        'Achievements' => $gameRating[RatingType::Achievement]['AverageRating'],
        'GameNumVotes' => $gameRating[RatingType::Game]['RatingCount'],
        'AchievementsNumVotes' => $gameRating[RatingType::Achievement]['RatingCount'],
    ],
]);
