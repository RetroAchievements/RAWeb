<?php

require_once __DIR__ . '/../../lib/bootstrap.php';

if (!ValidateAPIKey(seekGET('z'), seekGET('y'))) {
    echo "Invalid API Key";
    exit;
}

$gameId = seekGET('g');
$username = seekGET('u');

$results = getGameRankAndScore($gameId, $username);

echo json_encode($results);
