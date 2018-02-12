<?php 
	require_once('db.inc.php');
	
	$user = seekPOST( 'u' );
	$cookie = seekPOST( 'c' );
	$gameID = seekPOST( 'g' );
	if( !isset( $user ) )
	{
		$user = seekGET( 'u' );
		$cookie = seekGET( 'c' );
		$gameID = seekGET( 'g' );
	}
	
	if( validateUser_cookie( $user, $cookie, Permissions::Developer ) )
	{
		if( submitNewLeaderboard( $gameID, $lbID ) )
		{
			//	Good!
			header( "Location: http://" . AT_HOST . "/leaderboardList.php?g=$gameID&e=ok" );
			exit;
		}
		else
		{
			log_email( __FILE__ . "$user, $cookie, $gameID" );
			error_log( __FILE__ );
			error_log( "Issues2: user $user, cookie $cookie, gameID $gameID" );
			
			header( "Location: http://" . AT_HOST . "/leaderboardList.php&e=cannotcreate" );
			exit;
		}
	}
	else
	{
		error_log( __FILE__ );
		error_log( "Issues: user $user, cookie $cookie, $gameID" );
		header( "Location: http://" . AT_HOST . "/?e=badcredentials" );
		exit;
	}
	
	exit;
?>