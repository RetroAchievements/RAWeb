<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$userIn = requestInputPost('u');
$topicID = requestInputPost('t');
$commentPayload = requestInputPost('p');

if (!ValidatePOSTChars("tp")) {
    header("Location: " . getenv('APP_URL') . "/viewtopic.php?t=$topicID&e=invalidparams");
    exit;
}

if (validateFromCookie($user, $unused, $permissions, \RA\Permissions::Registered)) {
    if (submitTopicComment($user, $topicID, $commentPayload, $newCommentID)) {
        //	Good!
        header("Location: " . getenv('APP_URL') . "/viewtopic.php?t=$topicID&c=$newCommentID");
        exit;
    } else {
        // error_log(__FILE__);
        // error_log("Issues2: user $user, cookie $cookie, topicID $topicID, payload: $commentPayload");

        header("Location: " . getenv('APP_URL') . "/viewtopic.php?t=$topicID&e=issuessubmitting");
        exit;
    }
} else {
    // error_log(__FILE__);
    // error_log("Issues: userin $userIn, topicID $topicID, payload: $commentPayload");
    //log_email("Issues: userin $userIn, topicID $topicID, payload: $commentPayload");
    header("Location: " . getenv('APP_URL') . "/viewtopic.php?t=$topicID&e=badcredentials");
    exit;
}
