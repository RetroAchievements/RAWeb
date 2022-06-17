<?php

use RA\ArticleType;
use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$leaderboardId = requestInput('l', 0, 'integer');
$targetUser = requestInput('t');
$reason = requestInputPost('r');

// Only let jr. devs remove their own entries
if ($permissions == Permissions::JuniorDeveloper && $user != $targetUser) {
    return back()->withErrors(__('legacy.error.permissions'));
}

if (removeLeaderboardEntry($targetUser, $leaderboardId, $score)) {
    if ($targetUser != $user) {
        if (empty($reason)) {
            $commentText = 'removed "' . $targetUser . '"s entry of "' . $score . '" from this leaderboard';
        } else {
            $commentText = 'removed "' . $targetUser . '"s entry of "' . $score . '" from this leaderboard. Reason: ' . $reason;
        }
        addArticleComment("Server", ArticleType::Leaderboard, $leaderboardId, "\"$user\" $commentText.", $user);
    }
    return back()->with('success', __('legacy.success.ok'));
}

return back()->withErrors(__('legacy.error.error'));
