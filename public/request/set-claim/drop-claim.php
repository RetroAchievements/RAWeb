<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

use RA\ArticleType;
use RA\Permissions;

$gameID = requestInputQuery('i', null, 'integer');
$claimType = requestInputQuery('c', null, 'integer'); // 0 - Primary, 1 - Collaboration

if (authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    if (dropClaim($user, $gameID)) { // Check that the claim was successfully dropped
        if ($claimType == 0) {
            addArticleComment("Server", ArticleType::SetClaim, $gameID, "Primary claim dropped by " . $user);
        } else {
            addArticleComment("Server", ArticleType::SetClaim, $gameID, "Collaboration claim dropped by " . $user);
        }
        header("location: " . getenv('APP_URL') . "/game/$gameID");
    } else {
        header("location: " . getenv('APP_URL') . "/game/$gameID?e=error");
    }
} else {
    header("location: " . getenv('APP_URL') . "/game/$gameID?e=error");
}
