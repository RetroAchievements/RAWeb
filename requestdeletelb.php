<?php require_once('db.inc.php');
   
	// http://php.net/manual/en/security.database.sql-injection.php
	
	if( !ValidateGETChars( 'uig' ) )
	{
		echo "FAILED";
		return;
	}
	
	$source = seekGET( 'u' );
	$lbID = seekGET( 'i' );
	$gameID = seekGET( 'g' );
	
	getCookie( $user, $cookie );
	
	if( RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions, Permissions::Developer ) && 
		$source == $user && 
		validateUser_cookie( $user, $cookie, 2 ) )
	{
		if( requestDeleteLB( $lbID ) )
		{
			header( "Location: http://" . AT_HOST . "/leaderboardList.php?e=deleteok&g=$gameID" );
			exit;
		}
		else
		{
			echo "FAILED:Could not delete LB!";
			exit;
		}
	}
	else
	{
		echo "FAILED:Could not validate cookie - please login again!";
		exit;
	}
?>
