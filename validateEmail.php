<?php
	require_once('db.inc.php');
	
	if( !validateGetChars( "v" ) )
	{
		echo "FAILED";
		return;
	}
	
	$eCookie = $_GET['v'];

	if( validateEmailValidationString( $eCookie, $user ) )
	{
		//	Valid!
		generateCookie( $user, $cookieOut );
		header( "Location: http://retroachievements.org/?e=validatedEmail" );
	}
	else
	{
		//	Not valid!
		echo "Could not validate account!<br/>";
	}
?>