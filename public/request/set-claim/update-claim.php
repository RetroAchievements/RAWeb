<?php

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
    abort(401);
}

if (updateClaim($claimID, $claimType, $setType, $status, $special, $claimDate, $doneDate)) {
    addArticleComment("Server", ArticleType::SetClaim, $gameID, $comment);
    return response()->json(['message' => __('legacy.success.ok')]);
}

abort(400);
