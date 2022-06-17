<?php

use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    abort(401);
}

$gameID = requestInputQuery('i', null, 'integer');
$setRequestList = getUserRequestList($user);
$totalRequests = getUserRequestsInformation($user, $setRequestList, $gameID);
$totalRequests['gameRequests'] = getSetRequestCount($gameID);

if (toggleSetRequest($user, $gameID, $totalRequests['remaining'])) {
    return response()->json(['message' => __('legacy.success.ok')]);
}

abort(400);
