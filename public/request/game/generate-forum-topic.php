<?php
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidateGETChars("ucg")) {
    header("Location: " . getenv('APP_URL') . "/forum.php?e=invalidparams");
    exit;
}

$user = seekGET('u');
$cookie = seekGET('c');
$gameID = seekGET('g');

if (validateUser_cookie($user, $cookie, 1)) {
    if (generateGameForumTopic($user, $gameID, $forumTopicID)) {
        //	Good!
        header("Location: " . getenv('APP_URL') . "/viewtopic.php?t=$forumTopicID");
    } else {
        //error_log( __FILE__ );
        // error_log("Issues2: user $user, cookie $cookie, topicID $topicID, payload: $commentPayload");
        header("Location: " . getenv('APP_URL') . "/forum.php?e=issuessubmitting");
    }
} else {
    //error_log( __FILE__ );
    // error_log("Issues: user $user, cookie $cookie, topicID $topicID, payload: $commentPayload");
    header("Location: " . getenv('APP_URL') . "/forum.php?e=badcredentials");
}
