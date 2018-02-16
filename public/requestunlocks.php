<?php
	require_once __DIR__ . '/../lib/bootstrap.php';
	
	if( !ValidatePOSTChars( "utg" ) )
	{
		echo "FAILED";
		return;
	}
	
	$user = seekPOST('u');
	$token = seekPOST('t');
	$gameID = seekPOST('g');
	//settype( $gameID, 'integer' );
	$hardcoreMode = seekPOST( 'h', 0 );
	
	if( validateUser_app( $user, $token, $fbUser, 0 ) == TRUE )
	{
		echo "OK:";
		
		$numUnlocks = getUserUnlocks( $user, $gameID, $dataOut, $hardcoreMode );
		for( $i = 0; $i < $numUnlocks; $i++ )
			echo $dataOut[$i] . ":";
		
		return TRUE;
	}
	
	echo "FAILED: Invalid User/Password combination.\n";// . $user . "\n" . $pass . "\n" . $hashed . "\n";
	return FALSE;
?>
