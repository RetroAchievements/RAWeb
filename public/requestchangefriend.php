<?php require_once __DIR__ . '/../lib/bootstrap.php';
	
	// http://php.net/manual/en/security.database.sql-injection.php
	
	if( !ValidateGETChars( "ucfa" ) )
	{
		echo "FAILED";
		return;
	}
	
	$user 	= seekGET('u');
	$cookie = seekGET('c');
	$friend = seekGET('f');
	$action = seekGET('a');
	
	if( validateUser_cookie( $user, $cookie, 0 ) == TRUE )
	{
		$returnVal = ChangeFriendStatus( $user, $friend, $action );
		header( "Location: http://" . AT_HOST . "/User/$friend?e=$returnVal" );
	}
	else
	{
		header( "Location: http://" . AT_HOST . "/User/$friend?e=pleaselogin" );
		//echo "INVALID USER/PASS!";
	}
?>
