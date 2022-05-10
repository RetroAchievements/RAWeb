<?php

use RA\Permissions;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$gameID = requestInput('g');
$leaderboardID = requestInput('l');
$duplicateNumber = requestInput('n');

if (RA_ValidateCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
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
