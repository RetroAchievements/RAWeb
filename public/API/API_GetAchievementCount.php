<?php

require_once __DIR__ . '/../../vendor/autoload.php';

runPublicApiMiddleware();

$gameID = seekGET('i');

echo json_encode(getAchievementIDs($gameID));
