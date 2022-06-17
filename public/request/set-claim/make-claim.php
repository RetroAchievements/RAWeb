<?php

use RA\ArticleType;
use RA\ClaimSetType;
use RA\ClaimType;
use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$gameID = requestInputQuery('i', null, 'integer');
$claimType = requestInputQuery('c', null, 'integer'); // 0 - Primary, 1 - Collaboration
$setType = requestInputQuery('s', null, 'integer'); // 0 - New set, 1 - Revision
$createForumTopic = requestInputQuery('f', 0, 'integer');

$special = (int) checkIfSoleDeveloper($user, $gameID);
if (insertClaim($user, $gameID, $claimType, $setType, $special, $permissions)) {
    if ($claimType == ClaimType::Primary) {
        addArticleComment("Server", ArticleType::SetClaim, $gameID, ClaimType::toString(ClaimType::Primary) . " " . ($setType == ClaimSetType::Revision ? "revision" : "") . " claim made by " . $user);
    } else {
        addArticleComment("Server", ArticleType::SetClaim, $gameID, ClaimType::toString(ClaimType::Collaboration) . " " . ($setType == ClaimSetType::Revision ? "revision" : "") . " claim made by " . $user);
    }

    if ($createForumTopic && $permissions >= Permissions::Developer) {
        generateGameForumTopic($user, $gameID, $forumTopicID);

        return redirect(route('game.show', $gameID));
    }

    return back()->with('success', __('legacy.success.ok'));
}

return back()->withErrors(__('legacy.error.error'));
