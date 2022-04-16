<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/bootstrap.php';

runPublicApiMiddleware();

$user = requestInputQuery('u', null);

$retVal = [];

$retVal['Score'] = getScore($user);
$retVal['Rank'] = getUserRank($user);
$retVal['TotalRanked'] = countRankedUsers();

echo json_encode($retVal, JSON_THROW_ON_ERROR);
