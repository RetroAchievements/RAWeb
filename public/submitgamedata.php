#<?php 
	require_once __DIR__ . '/../lib/bootstrap.php';
	
	$gameID 	= seekPOST( 'i' );
	settype( $gameID, 'integer' );
	
	$developer 	= seekPOST( 'd' );
	$publisher 	= seekPOST( 'p' );
	$genre 		= seekPOST( 'g' );
	$released 	= seekPOST( 'r' );
	
	$richPresence= seekPOST( 'x' );
	
	$newGameAlt = seekPOST( 'n' );
	$removeGameAlt = seekPOST( 'm' );
	
	if( RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions, \RA\Permissions::SuperUser ) )
	{
		if( isset( $richPresence ) )
		{
			requestModifyRichPresence( $gameID, $richPresence );
			header( "location: http://" . AT_HOST . "/Game/$gameID?e=ok" );
			exit;
		}
		else if( isset( $newGameAlt ) || isset( $removeGameAlt ) )
		{
			//	new alt provided/alt to be removed
			error_log( "Provided $newGameAlt and $removeGameAlt to submitgamedata" );
			requestModifyGameAlt( $gameID, $newGameAlt, $removeGameAlt );
			header( "location: http://" . AT_HOST . "/Game/$gameID?e=ok" );
			exit;
		}
		else if( isset( $developer ) && isset( $publisher ) && isset( $genre ) && isset( $released ) )
		{
			requestModifyGameData( $gameID, $developer, $publisher, $genre, $released );
			header( "location: http://" . AT_HOST . "/Game/$gameID?e=ok" );
			exit;
		}
		else
		{
			//	unknown?
			header( "location: http://" . AT_HOST . "/Game/$gameID?e=unrecognised" );
			exit;
		}
	}
	else
	{
		header( "location: http://" . AT_HOST . "/Game/$gameID?e=notloggedin" );
		exit;
	}
?>
