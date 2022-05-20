<?php

use RA\Permissions;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$userIn = requestInputPost('u');
$topicID = requestInputPost('t');
$commentPayload = requestInputPost('p');

if (!ValidatePOSTChars("tp")) {
    header("Location: " . getenv('APP_URL') . "/viewtopic.php?t=$topicID&e=invalidparams");
    exit;
}

if (authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    if (submitTopicComment($user, $topicID, null, $commentPayload, $newCommentID)) {
        // Good!
        header("Location: " . getenv('APP_URL') . "/viewtopic.php?t=$topicID&c=$newCommentID");
        exit;
    } else {
        header("Location: " . getenv('APP_URL') . "/viewtopic.php?t=$topicID&e=issuessubmitting");
        exit;
    }
} else {
    header("Location: " . getenv('APP_URL') . "/viewtopic.php?t=$topicID&e=badcredentials");
    exit;
}
