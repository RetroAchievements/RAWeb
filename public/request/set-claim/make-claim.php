<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

use RA\ArticleType;
use RA\Permissions;

$gameID = requestInputQuery('i', null, 'integer');
$claimType = requestInputQuery('c', null, 'integer'); // 0 - Primary, 1 - Collaboration
$setType = requestInputQuery('s', null, 'integer'); // 0 - New set, 1 - Revision
$createForumTopic = requestInputQuery('f', 0, 'integer');

if (authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    $special = (int) checkIfSoleDeveloper($user, $gameID);
    if (insertClaim($user, $gameID, $claimType, $setType, $special, $permissions)) { // Check that the claim was successfully added
        if ($claimType == 0) {
            addArticleComment("Server", ArticleType::SetClaim, $gameID, "Primary " . ($setType == 1 ? "revision" : "") . " claim made by " . $user);
        } else {
            addArticleComment("Server", ArticleType::SetClaim, $gameID, "Collaboration " . ($setType == 1 ? "revision" : "") . " claim made by " . $user);
        }

        if ($createForumTopic && $permissions >= Permissions::Developer) { // Create forum topic if developer
            header("location: " . getenv('APP_URL') . "/request/game/generate-forum-topic.php?g=$gameID&f=1");
        } else {
            header("location: " . getenv('APP_URL') . "/game/$gameID");
        }
    } else {
        header("location: " . getenv('APP_URL') . "/game/$gameID?e=error");
    }
} else {
    header("location: " . getenv('APP_URL') . "/game/$gameID?e=error");
}
