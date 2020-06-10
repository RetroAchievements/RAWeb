<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

if (!ValidatePOSTChars("ugfv")) {
    echo "FAILED";
    return;
}

$author = seekPOST('u');
$gameID = seekPOST('g');
$field = seekPOST('f');
$value = seekPOST('v');

if (RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions, \RA\Permissions::Developer)) {
    if (requestModifyGame($author, $gameID, $field, $value)) {
        header("location: " . getenv('APP_URL') . "/Game/$gameID?e=modify_game_ok");
        exit;
    } else {
        header("location: " . getenv('APP_URL') . "/Game/$gameID?e=errors_in_modify_game");
        exit;
    }
}
