<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$leaderboardId = requestInput('l', 0, 'integer');
$targetUser = requestInput('t');
$reason = requestInputPost('r');
$returnUrl = getenv('APP_URL') . '/leaderboardinfo.php?i=' . $leaderboardId;

if (!RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions, \RA\Permissions::Developer)) {
    header('Location: ' . $returnUrl . '&success=false');
    return;
}

$response['Response'] = removeLeaderboardEntry($targetUser, $leaderboardId);
$response['Success'] = $response['Response']['Success'];
$response['Score'] = $response['Response']['Score'];

if ($response['Success']) {
    header('Location: ' . $returnUrl . '&success=true');
    if ($targetUser != $user) {
        if (empty($reason)) {
            $commentText = 'removed "' . $targetUser . '"s entry of "' . $response['Score'] . '" from this leaderboard';
        } else {
            $commentText = 'removed "' . $targetUser . '"s entry of "' . $response['Score'] . '" from this leaderboard. Reason: ' . $reason;
        }
        addArticleComment("Server", \RA\ArticleType::Leaderboard, $leaderboardId, "\"$user\" $commentText.", $user);
    }
    return;
}

header('Location: ' . $returnUrl . '&success=false');
