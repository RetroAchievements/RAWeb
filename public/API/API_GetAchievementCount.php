<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/bootstrap.php';

runPublicApiMiddleware();

$gameID = requestInputQuery('i');

echo json_encode(getAchievementIDs($gameID));
