<?php
require_once __DIR__ . '/../../lib/bootstrap.php';

if (!ValidateAPIKey(seekGET('z'), seekGET('y'))) {
    echo "Invalid API Key";
    exit;
}

$gameId = seekGET('g');
$username = seekGET('u');

$gameTopAchievers = getGameTopAchievers($gameId, 0, 10, $username);

echo json_encode($gameTopAchievers);
