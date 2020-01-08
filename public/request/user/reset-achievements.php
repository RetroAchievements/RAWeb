<?php
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidatePOSTChars("u")) {
    echo "FAILED";
    return;
}

$user = seekPOST('u', null);
$pass = seekPOST('p', null);
$gameID = seekPOST('g', null);
$achID = seekPOST('a', null);
$hardcoreMode = seekPOST('h', null);

/**
 * require password when resetting everything
 */
$requirePass = true;
if (isset($gameID) || isset($achID)) {
    $requirePass = false;
}

if ((!$requirePass) || validateUser($user, $pass, $fbUser, 0)) {
    if (isset($achID)) {
        if (resetSingleAchievement($user, $achID, $hardcoreMode)) {
            recalcScore($user);
            echo "OK";
        } else {
            echo "ERROR!";
        }
    } else {
        /**
         * NOTE: full reset deprecated until v2
         */
        if (empty($gameID)) {
            echo "ERROR!";
        }
        if (resetAchievements($user, $gameID) > 0) {
            recalcScore($user);
            echo "OK";
        } else {
            echo "ERROR!";
        }
    }
} else {
    echo "FAILED";
}
