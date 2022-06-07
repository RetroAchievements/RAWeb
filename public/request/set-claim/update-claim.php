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

if (authenticateFromCookie($user, $permissions, $userDetails, Permissions::Admin)) {
    if (updateClaim($claimID, $claimType, $setType, $status, $special, $claimDate, $doneDate)) {
        $comment = "$user updated $claimOwner's claim. ";
        $comment .= "Claim Type: " . ($claimType == 0 ? "Primary. " : "Collaboration. ");
        $comment .= "Set Type: " . ($setType == 0 ? "New. " : "Revision. ");
        switch ($status) {
            case 0:
                $comment .= "Claim Status: Active. ";
                break;
            case 1:
                $comment .= "Claim Status: Complete. ";
                break;
            case 2:
                $comment .= "Claim Status: Dropped. ";
                break;
            default:
                $comment .= "Claim Status: Active. ";
                break;
        }
        $comment .= "Special: " . $special . ". ";
        $comment .= "Claim Date: " . $claimDate . ". ";
        $comment .= "Finished date: " . $doneDate . ".";
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
