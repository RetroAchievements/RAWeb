<?php

require_once __DIR__ . '/../../vendor/autoload.php';

runPublicApiMiddleware();

$user = requestInputQuery('u', null);

$retVal = [];

$retVal['Score'] = getScore($user);
$retVal['Rank'] = getUserRank($user);
$retVal['TotalRanked'] = countRankedUsers($user);

echo json_encode($retVal);
