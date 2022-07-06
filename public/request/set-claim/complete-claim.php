<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

use RA\ArticleType;
use RA\Permissions;

$gameID = requestInputSanitized('i', null, 'integer');

if (authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    if (completeClaim($user, $gameID)) { // Check that the claim was successfully completed
        addArticleComment("Server", ArticleType::SetClaim, $gameID, "Claim completed by " . $user);

        // Send email to set requestors
        $requestors = getSetRequestorsList($gameID, true); // need this to get email and probably game name to pass in.
        foreach ($requestors as $requestor) {
            sendSetRequestEmail($requestor['Requestor'], $requestor['Email'], $gameID, $requestor['Title']);
        }
        header("location: " . getenv('APP_URL') . "/game/$gameID");
    } else {
        header("location: " . getenv('APP_URL') . "/game/$gameID?e=error");
    }
} else {
    header("location: " . getenv('APP_URL') . "/game/$gameID?e=error");
}
