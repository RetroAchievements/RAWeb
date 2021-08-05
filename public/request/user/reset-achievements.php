<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidatePOSTChars("u")) {
    echo "FAILED";
    return;
}

$user = requestInputPost('u', null);
$gameID = requestInputPost('g', null, 'integer');
$achID = requestInputPost('a', null, 'integer');

if (RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions)) {
    if (!empty($achID) && resetSingleAchievement($user, $achID)) {
        echo "OK";
    } elseif (!empty($gameID) && resetAchievements($user, $gameID) > 0) {
        echo "OK";
    } else {
        echo "ERROR!";
    }
} else {
    echo "FAILED";
}
