<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidateGETChars('uig')) {
    echo "FAILED";
    return;
}

$source = requestInputQuery('u');
$lbID = requestInputQuery('i');
$gameID = requestInputQuery('g');

getCookie($user, $cookie);

if (RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions, \RA\Permissions::Developer) &&
    $source == $user &&
    validateUser_cookie($user, $cookie, 2)) {
    if (requestDeleteLB($lbID)) {
        header("Location: " . getenv('APP_URL') . "/leaderboardList.php?e=deleteok&g=$gameID");
        exit;
    } else {
        echo "FAILED:Could not delete LB!";
        exit;
    }
} else {
    echo "FAILED:Could not validate cookie - please login again!";
    exit;
}
