<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidateGETChars("ucm")) {
    echo "FAILED";
    return;
}

$user = requestInputQuery('u');
$cookie = requestInputQuery('c');
$messageID = requestInputQuery('m');

if (validateUser_cookie($user, $cookie, 0) == true) {
    if (DeleteMessage($user, $messageID)) {
        header("Location: " . getenv('APP_URL') . "/inbox.php?e=deleteok");
        exit;
    } else {
        echo "FAILED:Could not delete message!";
    }
} else {
    echo "FAILED:Could not validate cookie - please login again!";
}
