<?php

use Illuminate\Support\Facades\Validator;
use LegacyApp\Site\Enums\Permissions;

if (!authenticateFromCookie($username, $permissions, $userDetails, Permissions::Registered)) {
    abort(401);
}

$input = Validator::validate(request()->post(), [
    'game' => 'required|integer|exists:mysql_legacy.GameData,ID',
]);

$gameID = (int) $input['game'];

$setRequestList = getUserRequestList($username);
$totalRequests = getUserRequestsInformation($username, $setRequestList, $gameID);
$totalRequests['gameRequests'] = getSetRequestCount($gameID);

if (toggleSetRequest($username, $gameID, $totalRequests['remaining'])) {
    return response()->json(['message' => __('legacy.success.ok')]);
}

abort(400);
