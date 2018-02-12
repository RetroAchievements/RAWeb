<?php
//	Internal: this is not public-facing!
require_once('../db.inc.php');

if( !ValidateAPIKey( seekGET( 'z' ), seekGET( 'y' ) ) )
{
	echo "Invalid API Key";
	exit;
}

$data = getConsoleIDs();

echo json_encode( $data );

?>