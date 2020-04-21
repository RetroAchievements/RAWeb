<?php

require_once __DIR__ . '/../../vendor/autoload.php';

runPublicApiMiddleware();

$gameId = seekGET('g');
$username = seekGET('u');

$gameTopAchievers = getGameTopAchievers($gameId, 0, 10, $username);

echo json_encode($gameTopAchievers);
