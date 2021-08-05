<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$gameID = requestInputQuery('i');

$gameRating = getGameRating($gameID);

if (!isset($gameRating[\RA\ObjectType::Game])) {
    $gameRating[\RA\ObjectType::Game]['AvgPct'] = 0.0;
    $gameRating[\RA\ObjectType::Game]['NumVotes'] = 0;
}
if (!isset($gameRating[\RA\ObjectType::Achievement])) {
    $gameRating[\RA\ObjectType::Achievement]['AvgPct'] = 0.0;
    $gameRating[\RA\ObjectType::Achievement]['NumVotes'] = 0;
}

$gameData = [];
$gameData['GameID'] = $gameID;
$gameData['Ratings']['Game'] = $gameRating[\RA\ObjectType::Game]['AvgPct'];
$gameData['Ratings']['Achievements'] = $gameRating[\RA\ObjectType::Achievement]['AvgPct'];
$gameData['Ratings']['GameNumVotes'] = $gameRating[\RA\ObjectType::Game]['NumVotes'];
$gameData['Ratings']['AchievementsNumVotes'] = $gameRating[\RA\ObjectType::Achievement]['NumVotes'];

echo json_encode($gameData);
