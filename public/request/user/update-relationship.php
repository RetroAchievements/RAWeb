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

    $referer = $_SERVER['HTTP_REFERER'] ?? getenv('APP_URL') . "/user/$friend";
    $pos = strpos($referer, '?');
    if ($pos !== false) {
        $referer = substr($referer, 0, $pos);
    }

    header("Location: $referer?e=$returnVal");
} else {
    header("Location: " . getenv('APP_URL') . "/user/$friend?e=pleaselogin");
}
