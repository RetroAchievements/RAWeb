<?php

use RA\Permissions;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

// Sanitise!
if (!ValidatePOSTChars("act")) {
    echo "FAILED";
    exit;
}

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    echo "FAILED!";
    exit;
}

$articleID = requestInputPost('a', null, 'integer');
$articleType = requestInputPost('t', null, 'integer');

$commentPayload = requestInputPost('c');

if (addArticleComment($user, $articleType, $articleID, $commentPayload)) {
    echo $articleID;
} else {
    echo "FAILED!";
}
