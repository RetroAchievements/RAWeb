<?php

require_once __DIR__ . '/../../vendor/autoload.php';

runPublicApiMiddleware();

$gameID = requestInputQuery('i');

echo json_encode(getAchievementIDs($gameID));
