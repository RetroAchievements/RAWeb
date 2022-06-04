<?php

use RA\Permissions;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$gameID = requestInputPost('i', null, 'integer');

$title = requestInputPost('t');

$developer = requestInputPost('d');
$publisher = requestInputPost('p');
$genre = requestInputPost('g');
$released = requestInputPost('r');

$richPresence = requestInputPost('x');

$newGameAlt = requestInputPost('n');
$removeGameAlt = requestInputPost('m');

$newForumTopic = requestInputPost('f');

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    header("location: " . getenv('APP_URL') . "/game/$gameID?e=notloggedin");
    exit;
}

// Only allow jr. devs if they are the sole author of the set
if ($permissions == Permissions::JuniorDeveloper) {
    if (!checkIfSoleDeveloper($user, $gameID)) {
        header("location: " . getenv('APP_URL') . "/game/$gameID?e=error");
        exit;
    }
}

$result = null;

if (isset($richPresence)) {
    $result = modifyGameRichPresence($user, $gameID, $richPresence);
} else if (isset($newGameAlt) || isset($removeGameAlt)) {
    // new alt provided/alt to be removed
    $result = modifyGameAlternatives($user, $gameID, $newGameAlt, $removeGameAlt);
} else if (isset($developer) && isset($publisher) && isset($genre) && isset($released)) {
    $result = modifyGameData($user, $gameID, $developer, $publisher, $genre, $released);
} else if (isset($newForumTopic)) {
    $result = modifyGameForumTopic($user, $gameID, $newForumTopic);
} else if (isset($title)) {
    if ($permissions == Permissions::JuniorDeveloper) {
        // Junior Developer not allowed to modify title, even if they are the sole author
        $result = false;
    } else {
        $result = modifyGameTitle($user, $gameID, $title);
    }
}

if ($result === true) {
    header("location: " . getenv('APP_URL') . "/game/$gameID?e=ok");
} elseif ($result == false) {
    header("location: " . getenv('APP_URL') . "/game/$gameID?e=error");
} else {
    // unknown?
    header("location: " . getenv('APP_URL') . "/game/$gameID?e=unrecognised");
}
