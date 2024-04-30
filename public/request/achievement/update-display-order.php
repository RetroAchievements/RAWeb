<?php

use App\Community\Enums\ClaimSetType;
use App\Enums\Permissions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    abort(401);
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'achievement' => 'required|integer',
    'number' => 'required|integer',
    'game' => 'required|integer',
]);

$achievementId = (int) $input['achievement'];
$gameId = (int) $input['game'];
$number = (int) $input['number'];

// Only allow jr. devs to update the display order if they are the sole author of the set or have the primary claim
if ($permissions == Permissions::JuniorDeveloper && (!checkIfSoleDeveloper($user, $gameId) && !hasSetClaimed($user, $gameId, true, ClaimSetType::NewSet))) {
    abort(403);
}

if (updateAchievementDisplayOrder($achievementId, $number)) {
    return response()->json(['message' => __('legacy.success.ok')]);
}

abort(400);
