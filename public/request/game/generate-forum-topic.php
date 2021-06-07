<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

use RA\Permissions;

if (!ValidateGETChars("g")) {
    header("Location: " . getenv('APP_URL') . "/forum.php?e=invalidparams");
    exit;
}

$gameID = requestInputQuery('g');

if (!RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions, Permissions::Developer)) {
    header("Location: " . getenv('APP_URL') . "/forum.php?e=badcredentials");
    return;
}

if (generateGameForumTopic($user, $gameID, $forumTopicID)) {
    //	Good!
    header("Location: " . getenv('APP_URL') . "/viewtopic.php?t=$forumTopicID");
} else {
    //error_log( __FILE__ );
    // error_log("Issues2: user $user, cookie $cookie, topicID $topicID, payload: $commentPayload");
    header("Location: " . getenv('APP_URL') . "/forum.php?e=issuessubmitting");
}
