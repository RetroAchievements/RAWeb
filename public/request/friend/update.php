<?php

require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidateGETChars("ucfa")) {
    echo "FAILED";
    return;
}

$user = seekGET('u');
$cookie = seekGET('c');
$friend = seekGET('f');
$action = seekGET('a');

if (validateUser_cookie($user, $cookie, 0) == true) {
    $returnVal = changeFriendStatus($user, $friend, $action);
    header("Location: " . getenv('APP_URL') . "/User/$friend?e=$returnVal");
} else {
    header("Location: " . getenv('APP_URL') . "/User/$friend?e=pleaselogin");
}
