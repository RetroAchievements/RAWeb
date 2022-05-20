<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidatePOSTChars("u")) {
    echo "FAILED";
    exit;
}

$user = requestInputPost('u', null);
$gameID = requestInputPost('g', null, 'integer');
$achID = requestInputPost('a', null, 'integer');

if (authenticateFromCookie($user, $permissions, $userDetails)) {
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
