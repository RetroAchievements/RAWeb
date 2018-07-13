<?php 
	require_once __DIR__ . '/../lib/bootstrap.php';
   
	// http://php.net/manual/en/security.database.sql-injection.php
	
	if( !ValidatePOSTChars( "ucdtm" ) )
	{
		echo "FAILED";
		return;
	}
	
	$user = $_POST["u"];
	$cookie = $_POST["c"];
	
	$recipient = $_POST["d"];
	$title = $_POST["t"];
	$payload = $_POST["m"];
	
	if( validateUser_cookie( $user, $cookie, 0 ) == TRUE )
	{
		if( CreateNewMessage( $user, $recipient, $title, $payload ) )
		{
			header( "Location: " . APP_URL . "/inbox.php?e=sentok" );
			exit;
			//echo "OK:Message sent to $recipient!";
		}
		else
		{
			echo "FAILED:Could not send message!";
		}
	}
	else
	{
		echo "FAILED:Could not validate cookie - please login again!";
	}
?>
