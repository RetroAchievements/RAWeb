<?php
//	Internal: this is not public-facing!
require_once('../db.inc.php');

if( !ValidateAPIKey( seekGET( 'z' ), seekGET( 'y' ) ) )
{
	echo "Invalid API Key";
	exit;
}

$consoleID = seekGET( 'i' );

getGamesList( $consoleID, $dataOut );

echo json_encode( $dataOut );
?>