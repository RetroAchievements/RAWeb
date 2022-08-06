<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

use RA\ArticleType;
use RA\Permissions;

$claimOwner = requestInputPost('o', null);
$claimID = requestInputPost('i', null, 'integer');
$gameID = requestInputPost('g', null, 'integer');
$claimType = requestInputPost('c', null, 'integer'); // 0 - Primary, 1 - Collaboration
$setType = requestInputPost('s', null, 'integer'); // 0 - New set, 1 - Revision
$status = requestInputPost('t', null, 'integer'); // 0 - Active, 1 - Complete, 2 - Dropped
$special = requestInputPost('e', null, 'integer'); // Special flag
$claimDate = requestInputPost('d', null); // Claim date
$doneDate = requestInputPost('f', null); // Done date
$comment = requestInputPost('m', null); // Auso comment

if (authenticateFromCookie($user, $permissions, $userDetails, Permissions::Admin)) {
    if (updateClaim($claimID, $claimType, $setType, $status, $special, $claimDate, $doneDate)) {
        addArticleComment("Server", ArticleType::SetClaim, $gameID, $comment);
        $success = true;
    } else {
        $success = false;
    }
} else {
    $success = false;
}

echo json_encode([
    'Success' => $success,
]);
