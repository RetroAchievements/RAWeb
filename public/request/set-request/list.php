<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

$gameID = seekGET('i');
$user = seekGET('u');

settype($gameID, 'integer');

$setRequestList = getUserRequestList($user);
$totalRequests = getUserRequestsInformation($user, $setRequestList, $gameID);
$totalRequests['gameRequests'] = getSetRequestCount($gameID);

echo json_encode($totalRequests);
