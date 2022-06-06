<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    echo 'FAILED';
    exit;
}

$messageID = (int) requestInputPost('m');
$messageReadStatus = (int) requestInputPost('r', 0);    // normally set as read

if (empty($messageID)) {
    echo 'FAILED';
    exit;
}

if (markMessageAsRead($user, $messageID, $messageReadStatus)) {
    echo "OK:" . $messageID;
    exit;
}

echo "FAILED!";
