<?php

use Illuminate\Support\Facades\Validator;

$input = Validator::validate(request()->post(), [
    'game' => 'required|integer|exists:mysql_legacy.GameData,ID',
    'user' => 'required|string|exists:mysql_legacy.UserAccounts,User',
]);

$gameID = (int) $input['game'];
$user = $input['user'];

$setRequestList = getUserRequestList($user);
$totalRequests = getUserRequestsInformation($user, $setRequestList, $gameID);
$totalRequests['gameRequests'] = getSetRequestCount($gameID);

return response()->json($totalRequests);
