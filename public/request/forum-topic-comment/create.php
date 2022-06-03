<?php

use RA\Permissions;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$topicID = requestInputPost('t');
$commentPayload = requestInputPost('p');

if (!ValidatePOSTChars("tp")) {
    header("Location: " . getenv('APP_URL') . "/viewtopic.php?t=$topicID&e=invalidparams");
    exit;
}

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    header("Location: " . getenv('APP_URL') . "/viewtopic.php?t=$topicID&e=badcredentials");
    exit;
}

if (submitTopicComment($user, $topicID, null, $commentPayload, $newCommentID)) {
    // Good!
    header("Location: " . getenv('APP_URL') . "/viewtopic.php?t=$topicID&c=$newCommentID");
    exit;
}

header("Location: " . getenv('APP_URL') . "/viewtopic.php?t=$topicID&e=issuessubmitting");
