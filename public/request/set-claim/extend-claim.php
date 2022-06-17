<?php

use RA\ArticleType;
use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$gameID = requestInputSanitized('i', null, 'integer');

if (extendClaim($user, $gameID)) { // Check that the claim was successfully added
    addArticleComment("Server", ArticleType::SetClaim, $gameID, "Claim extended by " . $user);
    return back()->with('success', __('legacy.success.ok'));
}

return back()->withErrors(__('legacy.error.error'));
