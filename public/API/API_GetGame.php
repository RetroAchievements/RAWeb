<?php

require_once __DIR__ . '/../../lib/bootstrap.php';

if (!ValidateAPIKey(seekGET('z'), seekGET('y'))) {
    echo "Invalid API Key";
    exit;
}

$gameID = seekGET('i');
$gameData = [];

getGameTitleFromID($gameID, $gameTitle, $consoleID, $consoleName, $forumTopicID, $gameData);

$gameData['GameTitle'] = $gameTitle;
$gameData['ConsoleID'] = $consoleID;
$gameData['Console'] = $consoleName;
$gameData['ForumTopicID'] = $forumTopicID;

echo jsonp_encode($gameData);
