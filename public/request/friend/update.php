<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

// TODO do not allow GET requests, POST only
if (!ValidateGETChars("fa")) {
    echo "FAILED";
    exit;
}

$friend = requestInputQuery('f');
$action = requestInputQuery('a');

if (authenticateFromCookie($user, $permissions, $userDetail)) {
    $returnVal = changeFriendStatus($user, $friend, $action);
    header("Location: " . getenv('APP_URL') . "/user/$friend?e=$returnVal");
} else {
    header("Location: " . getenv('APP_URL') . "/user/$friend?e=pleaselogin");
}
