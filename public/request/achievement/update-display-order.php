<?php

use Illuminate\Support\Facades\Validator;
use RA\ClaimSetType;
use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    abort(401);
}

$input = Validator::validate(request()->post(), [
    'achievement' => 'required|integer',
    'number' => 'required|integer',
    'game' => 'required|integer',
]);

$achievementId = $input['achievement'];
$gameId = $input['game'];
$number = $input['number'];

// Only allow jr. devs to update the display order if they are the sole author of the set or have the primary claim
if ($permissions == Permissions::JuniorDeveloper && (!checkIfSoleDeveloper($user, $gameId) && !hasSetClaimed($user, $gameId, true, ClaimSetType::NewSet))) {
    abort(403);
}

if (updateAchievementDisplayID((int) $achievementId, $number)) {
    return response()->json(['message' => __('legacy.success.ok')]);
}

abort(400);
