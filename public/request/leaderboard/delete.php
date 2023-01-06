<?php

use Illuminate\Support\Facades\Validator;
use LegacyApp\Site\Enums\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(request()->post(), [
    'leaderboard' => 'required|integer|exists:mysql_legacy.LeaderboardDef,ID',
]);

$lbId = (int) $input['leaderboard'];

if (requestDeleteLB($lbId)) {
    return back()->with('success', __('legacy.success.delete'));
}

return back()->withErrors(__('legacy.error.error'));
