<?php

require_once __DIR__ . '/../../lib/bootstrap.php';

runPublicApiMiddleware();

settype($gameId, 'integer');
$gameId = seekGET('g');
if ($gameId <= 0) {
    echo json_encode(['success' => false]);
    return;
}

$username = seekGET('z');

$gameTopAchievers = getGameTopAchievers($gameId, 0, 10, $username);

echo json_encode($gameTopAchievers);
