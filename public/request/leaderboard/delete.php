<?php

use App\Enums\Permissions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'leaderboard' => 'required|integer|exists:LeaderboardDef,ID',
]);

$lbId = (int) $input['leaderboard'];

if (requestDeleteLB($lbId)) {
    return back()->with('success', __('legacy.success.delete'));
}

return back()->withErrors(__('legacy.error.error'));
