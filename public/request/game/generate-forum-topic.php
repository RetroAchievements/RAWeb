<?php

use RA\Permissions;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

// TODO do not allow GET requests, POST only
if (!ValidateGETChars("g")) {
    header("Location: " . getenv('APP_URL') . "/forum.php?e=invalidparams");
    exit;
}

$gameID = requestInputQuery('g');

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    header("Location: " . getenv('APP_URL') . "/forum.php?e=badcredentials");
    exit;
}

if (generateGameForumTopic($user, $gameID, $forumTopicID)) {
    // Good!
    header("Location: " . getenv('APP_URL') . "/viewtopic.php?t=$forumTopicID");
} else {
    header("Location: " . getenv('APP_URL') . "/forum.php?e=issuessubmitting");
}
