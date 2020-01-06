<?php
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidatePOSTChars('g')) {
    echo "FAILED";
    return;
}

$gameID = seekPOST('g');
settype($gameID, 'integer');

if (validateFromCookie($user, $points, $permissions, \RA\Permissions::Unregistered)) {
    echo "OK:";

    $numUnlocks = getUserUnlocksDetailed($user, $gameID, $dataOut);
    foreach ($dataOut as $nextAwarded) {
        echo $nextAwarded['Title'] . " (" . $nextAwarded['Points'] . ")" . "_:_";        //	_:_ delim 1

        if ($nextAwarded['HardcoreMode'] == 1) {
            echo "h";
        }

        echo $nextAwarded['ID'] . "::";            //	::	delim 2
    }

    exit;
} else {
    echo "FAILED: Invalid User/Password combination.\n";
    exit;
}
