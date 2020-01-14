<?php

require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidatePOSTChars("ugfv")) {
    echo "FAILED";
    return;
}

$author = $_POST["u"];
$gameID = $_POST["g"];
$field = $_POST["f"];
$value = $_POST["v"];

if (RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions, \RA\Permissions::Developer)) {
    if (requestModifyGame($author, $gameID, $field, $value)) {
        header("location: " . getenv('APP_URL') . "/Game/$gameID?e=modify_game_ok");
        exit;
    } else {
        header("location: " . getenv('APP_URL') . "/Game/$gameID?e=errors_in_modify_game");
        exit;
    }
}
