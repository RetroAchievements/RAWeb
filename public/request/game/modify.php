<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidatePOSTChars("ugfv")) {
    echo "FAILED";
    return;
}

$author = requestInputPost('u');
$gameID = requestInputPost('g');
$field = requestInputPost('f');
$value = requestInputPost('v');

if (RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions, \RA\Permissions::Developer)) {
    if (requestModifyGame($author, $gameID, $field, $value)) {
        header("location: " . getenv('APP_URL') . "/game/$gameID?e=modify_game_ok");
        exit;
    } else {
        header("location: " . getenv('APP_URL') . "/game/$gameID?e=errors_in_modify_game");
        exit;
    }
}
