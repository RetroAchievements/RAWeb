<?php

use RA\GameAction;
use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    abort(401);
}

if (!ValidatePOSTChars("gfv")) {
    abort(400);
}

$gameID = (int) requestInputPost('g');
$field = (int) requestInputPost('f');
$value = requestInputPost('v');

// TODO split requests

switch ($field) {
    case GameAction::UnlinkHash:
        $result = removeHash($user, $gameID, $value);
        break;

    case GameAction::UpdateHash:
        $name = requestInputPost('n');
        $labels = requestInputPost('l');
        $result = updateHashDetails($user, $gameID, $value, $name, $labels);
        break;

    default:
        abort(400);
}

if (!$result) {
    abort(400);
}

return response()->json(['message' => __('legacy.success.ok')]);
