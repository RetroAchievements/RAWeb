<?php
require_once __DIR__ . '/../../../lib/bootstrap.php';

$user = seekPOST('u');
$cookie = seekPOST('c');
$gameID = seekPOST('g');
if (!isset($user)) {
    $user = seekGET('u');
    $cookie = seekGET('c');
    $gameID = seekGET('g');
}

if (validateUser_cookie($user, $cookie, \RA\Permissions::Developer)) {
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
} else {
    // error_log(__FILE__);
    // error_log("Issues: user $user, cookie $cookie, $gameID");
    header("Location: " . getenv('APP_URL') . "/?e=badcredentials");
    exit;
}
