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

use RA\ObjectType;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/bootstrap.php';

runPublicApiMiddleware();

$gameID = requestInputQuery('i');

$gameRating = getGameRating($gameID);

if (!isset($gameRating[ObjectType::Game])) {
    $gameRating[ObjectType::Game]['AverageRating'] = 0.0;
    $gameRating[ObjectType::Game]['RatingCount'] = 0;
}
if (!isset($gameRating[ObjectType::Achievement])) {
    $gameRating[ObjectType::Achievement]['AverageRating'] = 0.0;
    $gameRating[ObjectType::Achievement]['RatingCount'] = 0;
}

$gameData = [];
$gameData['GameID'] = $gameID;
$gameData['Ratings']['Game'] = $gameRating[ObjectType::Game]['AverageRating'];
$gameData['Ratings']['Achievements'] = $gameRating[ObjectType::Achievement]['AverageRating'];
$gameData['Ratings']['GameNumVotes'] = $gameRating[ObjectType::Game]['RatingCount'];
$gameData['Ratings']['AchievementsNumVotes'] = $gameRating[ObjectType::Achievement]['RatingCount'];

echo json_encode($gameData, JSON_THROW_ON_ERROR);
