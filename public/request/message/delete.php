<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidateGETChars("m")) {
    echo "FAILED";
    exit;
}

$messageID = requestInputQuery('m');

if (RA_ValidateCookie($user, $permissions, $userDetails)) {
    if (DeleteMessage($user, $messageID)) {
        header("Location: " . getenv('APP_URL') . "/inbox.php?e=deleteok");
        exit;
    } else {
        echo "FAILED:Could not delete message!";
    }
} else {
    echo "FAILED:Could not validate cookie - please login again!";
}
