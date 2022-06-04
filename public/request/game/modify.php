<?php

use RA\ArticleType;
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

if ($field === GameAction::UpdateHash) {
    $name = requestInputPost('n');
    $labels = requestInputPost('l');
    if (updateHashDetails($gameID, $value, $name, $labels)) {
        // Log hash update
        addArticleComment("Server", ArticleType::GameHash, $gameID, $value . " updated by " . $user . ". Description: \"" . $name . "\". Label: \"" . $labels . "\"");
        echo "OK";
        exit;
    }
    echo "FAILED!";
    exit;
}

if (modifyGame($user, $gameID, $field, $value)) {
    if ($field == GameAction::UnlinkHash) {
        // Only return status when unlinking hash
        echo "OK";
        exit;
    }
    header("location: " . getenv('APP_URL') . "/game/$gameID?e=modify_game_ok");
    exit;
} else {
    if ($field == GameAction::UnlinkHash) {
        // Only return status when unlinking hash
        echo "FAILED!";
        exit;
    }
    header("location: " . getenv('APP_URL') . "/game/$gameID?e=errors_in_modify_game");
    exit;
}
