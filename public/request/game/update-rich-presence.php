<?php

use App\Community\Enums\ClaimSetType;
use App\Enums\Permissions;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'game' => 'required|integer|exists:GameData,ID',
    'rich_presence' => 'nullable|string|max:60000',
]);

$gameId = (int) $input['game'];

$userModel = User::firstWhere('User', $user);

// Only allow jr. devs if they are the sole author of the set or have the primary claim
if (
    $permissions === Permissions::JuniorDeveloper
    && (!checkIfSoleDeveloper($userModel, $gameId) && !hasSetClaimed($userModel, $gameId, true, ClaimSetType::NewSet))
) {
    return back()->withErrors(__('legacy.error.permissions'));
}

if (modifyGameRichPresence($user, $gameId, (string) $input['rich_presence'])) {
    return back()->with('success', __('legacy.success.ok'));
}

return back()->withErrors(__('legacy.error.error'));
