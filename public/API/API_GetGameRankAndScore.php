<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/bootstrap.php';

runPublicApiMiddleware();

$gameId = requestInputQuery('g', null, 'integer');
if ($gameId <= 0) {
    echo json_encode(['success' => false]);
    return;
}

$username = requestInputQuery('z');
$type = requestInputQuery('t', 0, 'integer');

$gameTopAchievers = getGameTopAchievers($gameId, $username);

if ($type == 1) {
    echo json_encode($gameTopAchievers['Masters']);
} else {
    echo json_encode($gameTopAchievers['HighScores']);
}
