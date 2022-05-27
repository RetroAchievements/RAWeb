<?php

use RA\RatingType;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$gameID = requestInputQuery('i');

$gameRating = getGameRating($gameID);

$gameData = [];
$gameData['GameID'] = $gameID;
$gameData['Ratings']['Game'] = $gameRating[RatingType::Game]['AverageRating'];
$gameData['Ratings']['Achievements'] = $gameRating[RatingType::Achievement]['AverageRating'];
$gameData['Ratings']['GameNumVotes'] = $gameRating[RatingType::Game]['RatingCount'];
$gameData['Ratings']['AchievementsNumVotes'] = $gameRating[RatingType::Achievement]['RatingCount'];

echo json_encode($gameData, JSON_THROW_ON_ERROR);
