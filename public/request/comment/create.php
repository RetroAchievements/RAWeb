<?php

require_once __DIR__ . '/../../../lib/bootstrap.php';

use RA\Permissions;

//	Sanitise!
if (!ValidatePOSTChars("act")) {
    echo "FAILED";
    return;
}

if (!RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions, Permissions::Registered)) {
    echo "FAILED!";
    return;
}

$articleID = seekPOST('a');
$articleType = seekPOST('t');
settype($articleID, 'integer');
settype($articleType, 'integer');

$commentPayload = seekPOST('c');
$commentPayload = preg_replace('/[^(\x20-\x7F)]*/', '', $commentPayload);

if (addArticleComment($user, $articleType, $articleID, $commentPayload)) {
    // error_log(__FILE__ . " returning $articleID");
    echo $articleID;
} else {
    echo "FAILED!";
}
