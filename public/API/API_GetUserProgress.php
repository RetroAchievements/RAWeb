<?php
//	Internal: this is not public-facing!
require_once __DIR__ . '/../../lib/bootstrap.php';

if( !ValidateAPIKey( seekGET( 'z' ), seekGET( 'y' ) ) )
{
	echo "Invalid API Key";
	exit;
}

$user = seekGET( 'u', NULL );
$gameCSV = seekGET( 'i', "" );

getUserProgress( $user, $gameCSV, $data );

echo json_encode( $data );
