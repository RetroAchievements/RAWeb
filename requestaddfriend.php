<?php require_once('db.inc.php');
   
	// http://php.net/manual/en/security.database.sql-injection.php
	
	if( !ValidatePOSTChars( "utn" ) )
	{
		echo "FAILED";
		return;
	}
	
	$user = $_POST["u"];
	$token = $_POST["t"];
	
	$newFriend = $_POST["n"];
	
	if( validateUser_app( $user, $token, $fbUser, 0 ) == TRUE )
	{
		if( addFriend( $user, $newFriend ) )
		{
			echo "OK:Sent Friend Request!";
		}
		else
		{
			//	Issues! Check ErrorLog
		}
	}
	else
	{
		echo "INVALID USER/PASS!";
	}
?>
