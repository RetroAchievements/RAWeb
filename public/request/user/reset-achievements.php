<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$gameID = requestInputPost('g', null, 'integer');
$achID = requestInputPost('a', null, 'integer');

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    redirect(back() . '?e=error');
    exit;
}

if (!empty($achID) && resetSingleAchievement($user, $achID)) {
    redirect(back() . '?reset=ok');
    exit;
}

if (!empty($gameID) && resetAchievements($user, $gameID) > 0) {
    redirect(back() . '?reset=ok');
    exit;
}

redirect(back() . '?e=error');
