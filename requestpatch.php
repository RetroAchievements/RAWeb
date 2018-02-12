<?php
	require_once('db.inc.php');
	
	//	Sanitise!
	if( !ValidatePOSTChars( "ugf" ) )
	{
		echo "FAILED";
		return;
	}
	
	$user = $_POST['u'];
	
	$gameID = $_POST["g"];
	settype( $checksum, 'integer' );
	$flags = $_POST["f"];
	settype( $flags, 'integer' );
	$leaderboard = seekPOST( 'l', 0 );
	settype( $leaderboard, 'integer' );
	
	if( getPatch( $gameID, $flags, $user, $leaderboard ) )
	{
		//echo "OK";
	}
	else
	{
		echo "FAILED!";
	}
	
?>