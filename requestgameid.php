<?php
	require_once('db.inc.php');
	
	error_log( __FUNCTION__ );
	
	//	Sanitise!
	if( !ValidatePOSTChars( "um" ) )
	{
		echo "FAILED";
		return;
	}
	
	$user = $_POST['u'];
	$md5 = $_POST['m'];
	
	$gameID = GetGameIDFromMD5( $md5 );
	settype( $gameID, 'integer' );
	
	if( $gameID !== 0 )
	{
		echo "OK:" . $gameID;
	}
	else
	{
		echo "UNRECOGNISED";
	}
?>