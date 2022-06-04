<?php

use RA\GameAction;
use RA\Permissions;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidatePOSTChars("gfv")) {
    echo json_encode(['success' => false, 'error' => 'Bad request: parameters missing']);
    exit;
}

$gameID = (int) requestInputPost('g');
$field = (int) requestInputPost('f');
$value = requestInputPost('v');

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

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
        echo json_encode(['success' => false, 'error' => 'Bad request: invalid field (' . $field . ')'], JSON_THROW_ON_ERROR);
        exit;
}

if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed']);
}
