<?php

use RA\Permissions;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

// Sanitise!
if (!ValidatePOSTChars("act")) {
    echo "FAILED";
    return;
}

if (!RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions, Permissions::Registered)) {
    echo "FAILED!";
    return;
}

$articleID = requestInputPost('a', null, 'integer');
$articleType = requestInputPost('t', null, 'integer');

$commentPayload = requestInputPost('c');

if (addArticleComment($user, $articleType, $articleID, $commentPayload)) {
    echo $articleID;
} else {
    echo "FAILED!";
}
