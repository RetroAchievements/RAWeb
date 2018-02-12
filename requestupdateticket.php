<?php require_once('db.inc.php');
   
	//error_log( "access to " . __FILE__ );
	
	if( !ValidateGETChars( "uiv" ) )
	{
		error_log( "FAILED access to " . __FILE__ );
		echo "FAILED";
		return;
	}
	
	$source = seekGET( 'u' );
	$ticketID = seekGET( 'i' );
	settype( $ticketID, "integer" );
	$ticketVal = seekGET( 'v' );
	
	error_log( "$source resolving ticket ID $ticketID" );
	
	if( RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions, Permissions::Developer ) && 
		($source == $user) )
	{
		if( updateTicket( $user, $ticketID, $ticketVal ) )
		{
			//echo "OK";
			header( "Location: http://" . AT_HOST . "/ticketmanager.php?e=OK" );
		}
		else
		{
			error_log( __FILE__ . " failed?!" . var_dump( $_POST ) . var_dump( $_GET ) );
			echo "FAILED!";
		}
	}
	else
	{
		echo "Credentials appear incorrect, $source (given) is not '$user' (cookie), or permissions incorrect.";
		echo "Please log out and log back in, then try again. Sorry about this!";
	}
?>
