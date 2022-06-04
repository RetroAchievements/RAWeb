<?php

use RA\GameAction;
use RA\Permissions;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidatePOSTChars("gfv")) {
    echo "FAILED";
    exit;
}

$gameID = (int) requestInputPost('g');
$field = (int) requestInputPost('f');
$value = requestInputPost('v');

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    echo "FAILED!";
    exit;
}

switch ($field) {
    case GameAction::UnlinkHash:
        removeHash($user, $gameID, $value);
        echo "OK";
        exit;

    case GameAction::UpdateHash:
        $name = requestInputPost('n');
        $labels = requestInputPost('l');
        $result = updateHashDetails($user, $gameID, $value, $name, $labels);
        break;

    default:
        exit;
}

echo $result ? "OK" : "FAILED!";
