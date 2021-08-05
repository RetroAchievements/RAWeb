<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/bootstrap.php';

runPublicApiMiddleware();

$gameId = requestInputQuery('g');
$username = requestInputQuery('u');

$results = getGameRankAndScore($gameId, $username);

echo json_encode($results);
