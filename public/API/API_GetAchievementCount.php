<?php

require_once __DIR__ . '/../../lib/bootstrap.php';

runPublicApiMiddleware();

$gameID = seekGET('i');

echo json_encode(getAchievementIDs($gameID));
