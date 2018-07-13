<?php
	require_once __DIR__ . '/../lib/bootstrap.php';
	
	global $fbConn;
	
	$userInput = $_GET['u'];
	
	if( RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions ) && ($user == $userInput) )
	{
		$query = "UPDATE UserAccounts SET fbPrefs='0', fbUser='0' WHERE User='$user'";
		//echo $query . "<br/>";
		
		log_sql( $query );
		$dbResult = s_mysql_query( $query );
		if( $dbResult !== FALSE )
		{
			//	TBD: check this manually!?
			header( "location: " . getenv('APP_URL') . "/controlpanel.php" );
			//echo "Updated Successfully! $user is no longer associated with facebook.";
		}
		else
		{
			error_log( $query );
			error_log( "requestremovefb.php db access failed - update query fail!?" );
			echo "Failed, unknown error... please ensure you are logged in.";
		}
	}
	else
	{
		error_log( "requestremovefb.php failed - cannot verify cookie!?" );
		echo "Failed - cannot verify cookie, please ensure you are logged as $userInput first.";
	}
?>
