<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

// TODO do not allow GET requests, POST only
if (!ValidateGETChars("m")) {
    echo "FAILED";
    exit;
}

$messageID = requestInputQuery('m');

if (authenticateFromCookie($user, $permissions, $userDetails)) {
    if (DeleteMessage($user, $messageID)) {
        header("Location: " . getenv('APP_URL') . "/inbox.php?e=deleteok");
        exit;
    } else {
        echo "FAILED:Could not delete message!";
    }
} else {
    echo "FAILED:Could not validate cookie - please login again!";
}
