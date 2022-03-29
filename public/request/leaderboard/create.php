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

if (validateUser_cookie($user, $cookie, \RA\Permissions::JuniorDeveloper)) {
    if (isset($leaderboardID) && isset($duplicateNumber)) {
        if (duplicateLeaderboard($gameID, $leaderboardID, $duplicateNumber, $user)) {
            header("Location: " . getenv('APP_URL') . "/leaderboardList.php?g=$gameID&e=ok");
            exit;
        } else {
            header("Location: " . getenv('APP_URL') . "/leaderboardList.php&e=cannotcreate");
            exit;
        }
    } else {
        $lbID = null;
        if (submitNewLeaderboard($gameID, $lbID, $user)) {
            // Good!
            header("Location: " . getenv('APP_URL') . "/leaderboardList.php?g=$gameID&e=ok");
            exit;
        } else {
            header("Location: " . getenv('APP_URL') . "/leaderboardList.php&e=cannotcreate");
            exit;
        }
    }
} else {
    header("Location: " . getenv('APP_URL') . "/?e=badcredentials");
    exit;
}
