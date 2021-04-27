<?php

require_once __DIR__ . '/../../../lib/bootstrap.php';

$leaderboardId = seekPOSTorGET('l', 0, 'integer');
$targetUser = seekPOSTorGET('t');
$returnUrl = getenv('APP_URL') . '/leaderboardinfo.php?i=' . $leaderboardId;

if (!RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions, \RA\Permissions::Developer)) {
    header('Location: ' . $returnUrl . '&success=false');
    return;
}

if (removeLeaderboardEntry($targetUser, $leaderboardId)) {
    header('Location: ' . $returnUrl . '&success=true');
    $commentText = 'removed "' . $targetUser . '"s entry from this leaderboard';
    addArticleComment("Server", \RA\ArticleType::Leaderboard, $leaderboardId, "\"$user\" $commentText.", $user);
    return;
}

header('Location: ' . $returnUrl . '&success=false');
