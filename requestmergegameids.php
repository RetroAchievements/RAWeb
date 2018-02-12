<?php
	require_once('db.inc.php');
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
		header( "Location: http://" . AT_HOST . "/Game/$newGameID?e=merge_success" );
	}
	else
	{
		header( "Location: http://" . AT_HOST . "/Game/$newGameID?e=merge_failed" );
	}
	
?>