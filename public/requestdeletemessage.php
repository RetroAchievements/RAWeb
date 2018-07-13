<?php require_once __DIR__ . '/../lib/bootstrap.php';
   
	// http://php.net/manual/en/security.database.sql-injection.php
	
	if( !ValidateGETChars( "ucm" ) )
	{
		echo "FAILED";
		return;
	}
	
	$user = seekGET('u');
	$cookie = seekGET('c');
	$messageID = seekGET('m');
	
	if( validateUser_cookie( $user, $cookie, 0 ) == TRUE )
	{
		if( DeleteMessage( $user, $messageID ) )
		{
			header( "Location: " . APP_URL . "/inbox.php?e=deleteok" );
			exit;
		}
		else
		{
			echo "FAILED:Could not delete message!";
		}
	}
	else
	{
		echo "FAILED:Could not validate cookie - please login again!";
	}
?>
