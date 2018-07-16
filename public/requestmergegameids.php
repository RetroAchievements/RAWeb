<?php
	require_once __DIR__ . '/../lib/bootstrap.php';
	error_log( __FUNCTION__ );
	
	//	Sanitise!
	if( !ValidatePOSTChars( "ugn" ) )
	{
		echo "FAILED";
		return;
	}
	
	$user = seekPOST('u');
	$oldGameID = seekPOST('g');
	$newGameID = seekPOST('n');
	
	if( mergeGameID( $user, $oldGameID, $newGameID ) )
	{
		header( "Location: " . getenv('APP_URL') ."/Game/$newGameID?e=merge_success" );
	}
	else
	{
		header( "Location: " . getenv('APP_URL') ."/Game/$newGameID?e=merge_failed" );
	}
	
?>
