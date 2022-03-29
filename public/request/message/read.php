<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidatePOSTChars("um")) {
    return;
}

$user = requestInputPost('u');
$messageID = requestInputPost('m', null, 'integer');

$messageReadStatus = requestInputPost('r', 0);    // normally set as read

if (markMessageAsRead($user, $messageID, $messageReadStatus)) {
    echo "OK:" . $messageID;
} else {
    echo "FAILED!";
}
