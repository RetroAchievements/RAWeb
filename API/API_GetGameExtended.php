<?php
//	Internal: this is not public-facing!
require_once('../db.inc.php');
 
if( !ValidateAPIKey( seekGET( 'z' ), seekGET( 'y' ) ) )
{
	echo "Invalid API Key";
	exit;
}

$gameID = seekGET( 'i' );
getGameMetadata( $gameID, NULL, $achData, $gameData );

$gameData['Achievements'] = $achData;

echo json_encode( $gameData );
?>