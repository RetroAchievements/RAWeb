<?php
//	Internal: this is not public-facing!
require_once __DIR__ . '/../../lib/bootstrap.php';

if( !ValidateAPIKey( seekGET( 'z' ), seekGET( 'y' ) ) )
{
	echo "Invalid API Key";
	exit;
}

$consoleID = seekGET( 'i' );

getGamesList( $consoleID, $dataOut );

echo json_encode( utf8ize($dataOut) );
