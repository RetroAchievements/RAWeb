<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/bootstrap.php';

runPublicApiMiddleware();

$gameID = requestInputQuery('i');
$hardcore = requestInputQuery('h', 0, 'integer');
$requestedBy = requestInputQuery('z');
$flags = requestInputQuery('f', 3, 'integer');

echo json_encode(getAchievementDistribution($gameID, $hardcore, $requestedBy, $flags));
