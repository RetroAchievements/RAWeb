<?php

$gameID = requestInputQuery('i', null, 'integer');
$user = requestInputQuery('u');

$setRequestList = getUserRequestList($user);
$totalRequests = getUserRequestsInformation($user, $setRequestList, $gameID);
$totalRequests['gameRequests'] = getSetRequestCount($gameID);

return response()->json($totalRequests);
