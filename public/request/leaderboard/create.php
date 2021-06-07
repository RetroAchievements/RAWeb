<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$user = requestInputPost('u');
$cookie = requestInputPost('c');
$gameID = requestInputPost('g');
$leaderboardID = requestInputPost('l');
$duplicateNumber = requestInputPost('n');
if (!isset($user)) {
    $user = requestInputQuery('u');
    $cookie = requestInputQuery('c');
    $gameID = requestInputQuery('g');
    $leaderboardID = requestInputQuery('l');
    $duplicateNumber = requestInputQuery('n');
}

if (validateUser_cookie($user, $cookie, \RA\Permissions::Developer)) {
    if (isset($leaderboardID) && isset($duplicateNumber)) {
        if (duplicateLeaderboard($gameID, $leaderboardID, $duplicateNumber)) {
            header("Location: " . getenv('APP_URL') . "/leaderboardList.php?g=$gameID&e=ok");
            exit;
        } else {
            header("Location: " . getenv('APP_URL') . "/leaderboardList.php&e=cannotcreate");
            exit;
        }
    } else {
        if (submitNewLeaderboard($gameID, $lbID)) {
            //	Good!
            header("Location: " . getenv('APP_URL') . "/leaderboardList.php?g=$gameID&e=ok");
            exit;
        } else {
            //log_email(__FILE__ . "$user, $cookie, $gameID");
            // error_log(__FILE__);
            // error_log("Issues2: user $user, cookie $cookie, gameID $gameID");

            header("Location: " . getenv('APP_URL') . "/leaderboardList.php&e=cannotcreate");
            exit;
        }
    }
} else {
    // error_log(__FILE__);
    // error_log("Issues: user $user, cookie $cookie, $gameID");
    header("Location: " . getenv('APP_URL') . "/?e=badcredentials");
    exit;
}
