<?php
require_once('../db.inc.php');

if( !ValidateAPIKey( seekGET( 'z' ), seekGET( 'y' ) ) )
{
	echo "Invalid API Key";
	exit;
}

$dataOut = array();
$numFound = getTopUsersByScore( 10, $dataOut, NULL );

echo json_encode( $dataOut );
?>