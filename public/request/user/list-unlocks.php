<?php

use RA\Permissions;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidatePOSTChars('g')) {
    echo "FAILED";
    exit;
}

$gameID = requestInputPost('g', null, 'integer');

if (RA_ValidateCookie($user, $permissions, $userDetails, Permissions::Unregistered)) {
    echo "OK:";

    $numUnlocks = getUserUnlocksDetailed($user, $gameID, $dataOut);
    $achievementIds = [];
    foreach ($dataOut as $nextAwarded) {
        if (in_array($nextAwarded['ID'], $achievementIds)) {
            continue;
        }

        $achievementIds[] = $nextAwarded['ID'];
        echo $nextAwarded['Title'] . " (" . $nextAwarded['Points'] . ")" . "_:_";        // _:_ delim 1

        echo $nextAwarded['ID'] . "::";            // ::	delim 2
    }

    exit;
}

echo "FAILED: Invalid User/Password combination.\n";
