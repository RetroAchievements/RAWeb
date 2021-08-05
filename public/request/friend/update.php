<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidateGETChars("ucfa")) {
    echo "FAILED";
    return;
}

$user = requestInputQuery('u');
$cookie = requestInputQuery('c');
$friend = requestInputQuery('f');
$action = requestInputQuery('a');

if (validateUser_cookie($user, $cookie, 0) == true) {
    $returnVal = changeFriendStatus($user, $friend, $action);
    header("Location: " . getenv('APP_URL') . "/user/$friend?e=$returnVal");
} else {
    header("Location: " . getenv('APP_URL') . "/user/$friend?e=pleaselogin");
}
