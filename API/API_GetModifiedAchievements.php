<?php
//	Internal: this is not public-facing!
require_once('../db.inc.php');

if( !ValidateAPIKey( seekGET( 'z' ), seekGET( 'y' ) ) )
{
	echo "Invalid API Key";
	exit;
}

$user = seekGET( 'u', NULL );
$gameCSV = seekGET( 'i', "" );

getUserProgress( $user, $gameCSV, $data );

echo json_encode( $data );

?>