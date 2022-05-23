<?php

/*
 *  API_GetAchievementDistribution - returns a mapping of the number of players who have earned each quantity of achievements for a game
 *    i : game id
 *    h : hardcore - 1 to only query hardcore unlocks, 0 to query all unlocks (default: 0)
 *    f : flags - 3 for core achievements, 5 for unofficial (default: 3)
 *
 *  map
 *   string     [key]      number of achievements earned
 *    int        [value]   number of players who have earned that many achievements
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/bootstrap.php';

runPublicApiMiddleware();

$gameID = requestInputQuery('i');
$hardcore = requestInputQuery('h', 0, 'integer');
$requestedBy = requestInputQuery('z');
$flags = requestInputQuery('f', 3, 'integer');

echo json_encode(getAchievementDistribution($gameID, $hardcore, $requestedBy, $flags), JSON_THROW_ON_ERROR);
