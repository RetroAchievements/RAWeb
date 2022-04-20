<?php

use RA\ObjectType;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$gameID = requestInputQuery('i');

$gameRating = getGameRating($gameID);

$gameData = [];
$gameData['GameID'] = $gameID;
$gameData['Ratings']['Game'] = $gameRating[ObjectType::Game]['AvgPct'];
$gameData['Ratings']['Achievements'] = $gameRating[ObjectType::Achievement]['AvgPct'];
$gameData['Ratings']['GameNumVotes'] = $gameRating[ObjectType::Game]['NumVotes'];
$gameData['Ratings']['AchievementsNumVotes'] = $gameRating[ObjectType::Achievement]['NumVotes'];

echo json_encode($gameData, JSON_THROW_ON_ERROR);
