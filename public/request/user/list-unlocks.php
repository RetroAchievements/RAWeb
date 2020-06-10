<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

if (!ValidatePOSTChars('g')) {
    echo "FAILED";
    return;
}

$gameID = seekPOST('g');
settype($gameID, 'integer');

if (validateFromCookie($user, $points, $permissions, \RA\Permissions::Unregistered)) {
    echo "OK:";

    $numUnlocks = getUserUnlocksDetailed($user, $gameID, $dataOut);
    $achievementIds = [];
    foreach ($dataOut as $nextAwarded) {
        if (in_array($nextAwarded['ID'], $achievementIds)) {
            continue;
        }

        $achievementIds[] = $nextAwarded['ID'];
        echo $nextAwarded['Title'] . " (" . $nextAwarded['Points'] . ")" . "_:_";        //	_:_ delim 1

        echo $nextAwarded['ID'] . "::";            //	::	delim 2
    }

    exit;
}

echo "FAILED: Invalid User/Password combination.\n";
