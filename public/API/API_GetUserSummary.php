<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/bootstrap.php';

runPublicApiMiddleware();

$user = requestInputQuery('u', null);
$recentGamesPlayed = requestInputQuery('g', 5);
$recentAchievementsEarned = requestInputQuery('a', 10);

$retVal = [];
getUserPageInfo($user, $retVal, $recentGamesPlayed, $recentAchievementsEarned, null);

if (!$retVal) {
    http_response_code(404);
    echo json_encode([
        'ID' => null,
        'User' => $user,
    ]);
    exit;
}

getAccountDetails($user, $userDetails);

$retVal['ID'] = $userDetails['ID'];
$retVal['Points'] = $userDetails['RAPoints'];
$retVal['Motto'] = $userDetails['Motto'];
$retVal['UserPic'] = "/UserPic/" . $user . ".png";
$retVal['Rank'] = getUserRank($user);
$retVal['TotalRanked'] = countRankedUsers();

//	Find out if we're online or offline
$retVal['LastActivity'] = getActivityMetadata($userDetails['LastActivityID']);

$lastUpdate = (int) date("U", strtotime($retVal['LastActivity']['lastupdate']));
$now = (int) date("U");

$status = ($lastUpdate + 600) > $now ? "Online" : "Offline";

$retVal['Status'] = $status;

//	Just from a security/polish point of view:
unset($retVal['Friendship'], $retVal['FriendReciprocation']);

echo json_encode($retVal);
