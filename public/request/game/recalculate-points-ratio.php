<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$gameID = requestInputQuery('g');

if (RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions, \RA\Permissions::Developer)) {
    if (recalculateTrueRatio($gameID)) {
        header("location: " . getenv('APP_URL') . "/game/$gameID?e=recalc_points_ratio_ok");
        exit;
    } else {
        header("location: " . getenv('APP_URL') . "/game/$gameID?e=recalc_points_ratio_error");
        exit;
    }
}
