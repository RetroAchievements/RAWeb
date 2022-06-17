<?php

use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

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

// Only allow jr. devs if they are the sole author of the set
if ($permissions == Permissions::JuniorDeveloper) {
    if (!checkIfSoleDeveloper($user, $gameID)) {
        return back()->withErrors(__('legacy.error.permissions'));
    }
}

$result = null;

if (isset($richPresence)) {
    $result = modifyGameRichPresence($user, $gameID, $richPresence);
} elseif (isset($newGameAlt) || isset($removeGameAlt)) {
    modifyGameAlternatives($user, $gameID, $newGameAlt, $removeGameAlt);
    $result = true;
} elseif (isset($developer) && isset($publisher) && isset($genre) && isset($released)) {
    $result = modifyGameData($user, $gameID, $developer, $publisher, $genre, $released);
} elseif (isset($newForumTopic)) {
    $result = modifyGameForumTopic($user, $gameID, $newForumTopic);
} elseif (isset($title)) {
    if ($permissions == Permissions::JuniorDeveloper) {
        // Junior Developer not allowed to modify title, even if they are the sole author
        $result = false;
    } else {
        $result = modifyGameTitle($user, $gameID, $title);
    }
}

if (!$result) {
    return back()->withErrors(__('legacy.error.error'));
}

return back()->with('success', __('legacy.success.ok'));
