<?php

use RA\ArticleType;
use RA\ClaimType;
use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$gameID = requestInputQuery('i', null, 'integer');
$claimType = requestInputQuery('c', null, 'integer'); // 0 - Primary, 1 - Collaboration

if (dropClaim($user, $gameID)) { // Check that the claim was successfully dropped
    if ($claimType == ClaimType::Primary) {
        addArticleComment("Server", ArticleType::SetClaim, $gameID, "Primary claim dropped by " . $user);
    } else {
        addArticleComment("Server", ArticleType::SetClaim, $gameID, "Collaboration claim dropped by " . $user);
    }
    return back()->with('success', __('legacy.success.ok'));
}

return back()->withErrors(__('legacy.error.error'));
