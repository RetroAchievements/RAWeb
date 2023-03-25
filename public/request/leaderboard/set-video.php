<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use LegacyApp\Community\Enums\ArticleType;
use LegacyApp\Site\Enums\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'user' => 'required|string|exists:mysql_legacy.UserAccounts,User',
    'leaderboard' => 'required|integer|exists:mysql_legacy.LeaderboardDef,ID',
    'video' => 'nullable|active_url',
]);

$leaderboardId = (int) $input['leaderboard'];
$targetUser = $input['user'];
$video = $input['video'];

// You can only set video links for your own leaderboard entries.
if ($user !== $targetUser) {
    return back()->withErrors(__('legacy.error.permissions'));
}

if (SetLeaderboardEntryVideo($targetUser, $leaderboardId, $video)) {
    return back()->with('success', __('legacy.success.ok'));
}

return back()->withErrors(__('legacy.error.error'));
