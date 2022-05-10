<?php

use RA\Permissions;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidateGETChars('uig')) {
    echo "FAILED";
    exit;
}

$source = requestInputQuery('u');
$lbID = requestInputQuery('i');
$gameID = requestInputQuery('g');

getCookie($user, $cookie);

if (RA_ValidateCookie($user, $permissions, $userDetails, Permissions::Developer) &&
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
