<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$gameID = requestInputQuery('i', null, 'integer');
$user = requestInputQuery('u');

$setRequestList = getUserRequestList($user);
$totalRequests = getUserRequestsInformation($user, $setRequestList, $gameID);
$totalRequests['gameRequests'] = getSetRequestCount($gameID);

echo json_encode($totalRequests);
