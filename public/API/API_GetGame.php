<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/bootstrap.php';

runPublicApiMiddleware();

$gameID = requestInputQuery('i');
$gameData = [];

getGameTitleFromID($gameID, $gameTitle, $consoleID, $consoleName, $forumTopicID, $gameData);

$gameData['GameTitle'] = $gameTitle;
$gameData['ConsoleID'] = $consoleID;
$gameData['Console'] = $consoleName;
$gameData['ForumTopicID'] = $forumTopicID;

echo json_encode($gameData);
