<?php
//	Internal: this is not public-facing!
require_once('../db.inc.php');

if( !ValidateAPIKey( seekGET( 'z' ), seekGET( 'y' ) ) )
{
	echo "Invalid API Key";
	exit;
}

$gameID = seekGET( 'i' );
$gameData = array();

getGameTitleFromID( $gameID, $gameTitle, $consoleID, $consoleName, $forumTopicID, $gameData );

$gameData['GameTitle'] = $gameTitle;
$gameData['ConsoleID'] = $consoleID;
$gameData['Console'] = $consoleName;
$gameData['ForumTopicID'] = $forumTopicID;

echo json_encode( $gameData );
?>