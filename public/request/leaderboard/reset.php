<?php

use App\Community\Enums\ArticleType;
use App\Site\Enums\Permissions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'leaderboard' => 'required|integer|exists:LeaderboardDef,ID',
]);

$lbId = (int) $input['leaderboard'];

requestResetLB($lbId);

$commentText = 'reset all entries for this leaderboard';
addArticleComment("Server", ArticleType::Leaderboard, $lbId, "\"$user\" $commentText.", $user);

return back()->with('success', __('legacy.success.ok'));
