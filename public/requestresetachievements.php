<?php
	require_once __DIR__ . '/../lib/bootstrap.php';
	
	error_log( __FUNCTION__ );
	
	//	Sanitise!
	if( !ValidatePOSTChars( "u" ) )
	{
		echo "FAILED";
		return;
	}
	
	$user = seekPOST( 'u', NULL );
	$pass = seekPOST( 'p', NULL );
	$gameID = seekPOST( 'g', NULL );
	$achID = seekPOST( 'a', NULL );
	$hardcoreMode = seekPOST( 'h', NULL );
	
	$requirePass = TRUE;
	if( isset( $gameID ) || isset( $achID ) )
		$requirePass = FALSE;
	
	if( (!$requirePass) || validateUser( $user, $pass, $fbUser, 0 ) == TRUE )
	{
		if( isset( $achID ) )
		{
			if( resetSingleAchievement( $user, $achID, $hardcoreMode ) )
			{
				//	Inject sneaky recalc:
				recalcScore( $user );
				echo "OK";
				//header( "Location: https://" . AT_HOST . "/controlpanel.php?e=resetok" );
			}
			else
			{
				echo "ERROR!";
				//header( "Location: https://" . AT_HOST . "/controlpanel.php?e=resetfailed" );
			}
		}
		else
		{
			if( resetAchievements( $user, $gameID ) > 0 )
			{
				recalcScore( $user );
				echo "OK";
				//header( "Location: https://" . AT_HOST . "/controlpanel.php?e=resetok" );
			}
			else
			{
				echo "ERROR!";
				//header( "Location: https://" . AT_HOST . "/controlpanel.php?e=resetfailed" );
			}
		}
	}
	else
	{
		echo "FAILED";
	}
?>
