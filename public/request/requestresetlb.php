<?php
require_once __DIR__ . '/../../lib/bootstrap.php';

if (!ValidateGETChars("ui")) {
    echo "FAILED! (POST)";
    exit;
}

$source = seekGET('u');
$lbid = seekGET('i');

//	Double check cookie as well

if (RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions, \RA\Permissions::Developer) &&
    ($source == $user)) {
    requestResetLB($lbid);

    header("location: " . getenv('APP_URL') . "/leaderboardinfo.php?i=$lbid&e=success");
    exit;
} else {
    header("location: " . getenv('APP_URL') . "/leaderboardinfo.php?i=$lbid&e=failed");
    exit;
}
