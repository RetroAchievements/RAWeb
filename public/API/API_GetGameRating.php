<?php

use RA\ObjectType;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/bootstrap.php';

runPublicApiMiddleware();

$gameID = requestInputQuery('i');

$gameRating = getGameRating($gameID);

if (!isset($gameRating[ObjectType::Game])) {
    $gameRating[ObjectType::Game]['AvgPct'] = 0.0;
    $gameRating[ObjectType::Game]['NumVotes'] = 0;
}
if (!isset($gameRating[ObjectType::Achievement])) {
    $gameRating[ObjectType::Achievement]['AvgPct'] = 0.0;
    $gameRating[ObjectType::Achievement]['NumVotes'] = 0;
}

$gameData = [];
$gameData['GameID'] = $gameID;
$gameData['Ratings']['Game'] = $gameRating[ObjectType::Game]['AvgPct'];
$gameData['Ratings']['Achievements'] = $gameRating[ObjectType::Achievement]['AvgPct'];
$gameData['Ratings']['GameNumVotes'] = $gameRating[ObjectType::Game]['NumVotes'];
$gameData['Ratings']['AchievementsNumVotes'] = $gameRating[ObjectType::Achievement]['NumVotes'];

echo json_encode($gameData, JSON_THROW_ON_ERROR);
