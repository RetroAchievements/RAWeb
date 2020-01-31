<?php

require_once __DIR__ . '/../../lib/bootstrap.php';

runPublicApiMiddleware();

$gameId = seekGET('g');
$username = seekGET('u');

$results = getGameRankAndScore($gameId, $username);

echo json_encode($results);
