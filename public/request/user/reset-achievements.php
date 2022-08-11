<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$gameID = requestInputPost('g', null, 'integer');
$achID = requestInputPost('a', null, 'integer');

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    exit;
}

if (!empty($achID) && resetSingleAchievement($user, $achID)) {
    echo "OK";
    exit;
}

if (!empty($gameID) && resetAchievements($user, $gameID) > 0) {
    echo "OK";
    exit;
}
