<?php

require_once __DIR__ . '/../../vendor/autoload.php';

runPublicApiMiddleware();

settype($gameId, 'integer');
$gameId = seekGET('g');
if ($gameId <= 0) {
    echo json_encode(['success' => false]);
    return;
}

$username = seekGET('z');
$type = seekGET('t', 0);
settype($type, 'integer');

$gameTopAchievers = getGameTopAchievers($gameId, 0, 10, $username, $type);

echo json_encode($gameTopAchievers);
