<?php
//	Internal: this is not public-facing!
require_once __DIR__ . '/../../lib/bootstrap.php';

if( !ValidateAPIKey( seekGET( 'z' ), seekGET( 'y' ) ) )
{
	echo "Invalid API Key";
	exit;
}

$user = seekGET( 'u', NULL );
$friends = seekGET( 'f', NULL );
$count = seekGET( 'c', 10 );
$offset = seekGET( 'o', 0 );

//	Sensible caps
if( $count > 100 )
	$count = 100;

$type = 'global';

if( isset( $user ) )
{
	if( isset( $friends ) )
		$type = 'friends';
	else
		$type = 'individual';
}

getFeed( $user, $count, $offset, $feedData, 0, $type );

echo json_encode( $feedData );
