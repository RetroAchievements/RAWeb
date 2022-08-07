<?php

/*
 *  API_GetUserRankAndScore
 *    u : username
 *
 *  int        Score           number of hardcore points the user has
 *  int        SoftcoreScore   number of softcore points the user has
 *  int?       Rank            user's site rank
 *  string     TotalRanked     total number of ranked users
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/bootstrap.php';

runPublicApiMiddleware();

$user = requestInputQuery('u', null);

$retVal = [];

$retVal['Score'] = 0;
if (getPlayerPoints($user, $userPoints)) {
    $retVal['Score'] = $userPoints['RAPoints'];
    $retVal['SoftcoreScore'] = $userPoints['RASoftcorePoints'];
}
$retVal['Rank'] = getUserRank($user);
$retVal['TotalRanked'] = countRankedUsers();

echo json_encode($retVal, JSON_THROW_ON_ERROR);
