<?php

use RA\Permissions;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$gameID = requestInputQuery('i', null, 'integer');

if (RA_ValidateCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    $setRequestList = getUserRequestList($user);
    $totalRequests = getUserRequestsInformation($user, $setRequestList, $gameID);
    $totalRequests['gameRequests'] = getSetRequestCount($gameID);

    $success = toggleSetRequest($user, $gameID, $totalRequests['remaining']);
} else {
    $success = false;
}

echo json_encode([
    'Success' => $success,
], JSON_THROW_ON_ERROR);
