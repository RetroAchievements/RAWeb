<?php

use RA\ArticleType;
use RA\Permissions;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

// TODO do not allow GET requests, POST only
if (!ValidateGETChars("ui")) {
    echo "FAILED! (POST)";
    exit;
}

$source = requestInputQuery('u');
$lbid = requestInputQuery('i');

// Double check cookie as well

if (authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer) &&
    ($source == $user)) {
    requestResetLB($lbid);

    header("location: " . getenv('APP_URL') . "/leaderboardinfo.php?i=$lbid&e=success");
    $commentText = 'reset all entries for this leaderboard';
    addArticleComment("Server", ArticleType::Leaderboard, $lbid, "\"$user\" $commentText.", $user);
    exit;
} else {
    header("location: " . getenv('APP_URL') . "/leaderboardinfo.php?i=$lbid&e=failed");
    exit;
}
