<?php
require_once __DIR__ . '/../lib/bootstrap.php';

error_log(__FUNCTION__);

//	Sanitise!
if (!ValidatePOSTChars("u")) {
    echo "FAILED";
    return;
}

$user = seekPOST('u', null);
$pass = seekPOST('p', null);
$gameID = seekPOST('g', null);
$achID = seekPOST('a', null);
$hardcoreMode = seekPOST('h', null);

$requirePass = true;
if (isset($gameID) || isset($achID)) {
    $requirePass = false;
}

if ((!$requirePass) || validateUser($user, $pass, $fbUser, 0) == true) {
    if (isset($achID)) {
        if (resetSingleAchievement($user, $achID, $hardcoreMode)) {
            //	Inject sneaky recalc:
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
