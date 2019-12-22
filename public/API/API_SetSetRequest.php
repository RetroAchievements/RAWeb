<?php
require_once __DIR__ . '/../../lib/bootstrap.php';

$gameID = seekGET( 'i' );
$user = seekGET( 'u' );

settype( $gameID, 'integer' );

$setRequestList = getUserRequestList($user, 1, 1);
$totalRequests = getUserRequestsInformation($user, $setRequestList, $gameID);
$totalRequests['gameRequests'] = getSetRequestCount($gameID);

$success = toggleSetRequest($user, $gameID, $totalRequests['remaining']);

echo json_encode( $success );
