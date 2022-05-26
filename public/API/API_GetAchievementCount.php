<?php

/*
 *  API_GetAchievementCount - returns the achievements associated to a game
 *    i : game id
 *
 *  int        GameID                     unique identifier of the game
 *  array      AchievementIDs
 *   int        [value]                   unique identifier of an achievement associated to the game
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/bootstrap.php';

runPublicApiMiddleware();

$gameID = requestInputQuery('i');

echo json_encode(getAchievementIDs($gameID), JSON_THROW_ON_ERROR);
