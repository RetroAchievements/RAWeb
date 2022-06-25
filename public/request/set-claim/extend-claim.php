<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

use RA\ArticleType;
use RA\Permissions;

$gameID = requestInputSanitized('i', null, 'integer');

if (authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    if (extendClaim($user, $gameID)) { // Check that the claim was successfully added
        addArticleComment("Server", ArticleType::SetClaim, $gameID, "Claim extended by " . $user);
        header("location: " . getenv('APP_URL') . "/game/$gameID");
    } else {
        header("location: " . getenv('APP_URL') . "/game/$gameID?e=error");
    }
} else {
    header("location: " . getenv('APP_URL') . "/game/$gameID?e=error");
}
