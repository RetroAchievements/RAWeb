<?php

use RA\ArticleType;
use RA\Permissions;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidatePOSTChars("ugfv")) {
    echo "FAILED";
    exit;
}

$author = requestInputPost('u');
$gameID = requestInputPost('g');
$field = requestInputPost('f');
$value = requestInputPost('v');

if (authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    if ($field == 4) {
        $name = requestInputPost('n');
        $labels = requestInputPost('l');
        if (updateHashDetails($gameID, $value, $name, $labels)) {
            // Log hash update
            addArticleComment("Server", ArticleType::GameHash, $gameID, $value . " updated by " . $user . ". Description: \"" . $name . "\". Label: \"" . $labels . "\"");
            echo "OK";
        } else {
            echo "FAILED!";
        }
        exit;
    }

    if (modifyGame($author, $gameID, $field, $value)) {
        if ($field == 3) { // Only return status when unlinking hash
            echo "OK";
            exit;
        }
        header("location: " . getenv('APP_URL') . "/game/$gameID?e=modify_game_ok");
        exit;
    } else {
        if ($field == 3) { // Only return status when unlinking hash
            echo "FAILED!";
            exit;
        }
        header("location: " . getenv('APP_URL') . "/game/$gameID?e=errors_in_modify_game");
        exit;
    }
}
