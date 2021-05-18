<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

$leaderboardId = requestInput('l', 0, 'integer');
$targetEntry = explode(",", requestInput('t'));
$targetUser = $targetEntry[0];
$targetUserScore = $targetEntry[1];
$reason = requestInputPost('r');
$returnUrl = getenv('APP_URL') . '/leaderboardinfo.php?i=' . $leaderboardId;

if (!RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions, \RA\Permissions::Developer)) {
    header('Location: ' . $returnUrl . '&success=false');
    return;
}

if (removeLeaderboardEntry($targetUser, $leaderboardId)) {
    header('Location: ' . $returnUrl . '&success=true');
    if ($targetUser != $user) {
        if ($reason === '') {
            $commentText = 'removed "' . $targetUser . '"s entry of "' . $targetUserScore . '" from this leaderboard';
        } else {
            $commentText = 'removed "' . $targetUser . '"s entry of "' . $targetUserScore . '" from this leaderboard. Reason: ' . $reason;
        }
        addArticleComment("Server", \RA\ArticleType::Leaderboard, $leaderboardId, "\"$user\" $commentText.", $user);
    }
    return;
}

header('Location: ' . $returnUrl . '&success=false');
