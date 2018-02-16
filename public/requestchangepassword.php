<?php 
	require_once __DIR__ . '/../lib/bootstrap.php';
	
	if( !ValidatePOSTChars( "uxy" ) )
	{
		error_log( __FILE__ );
		error_log( "Cannot validate uxy input..." );
		header( "Location: http://" . AT_HOST . "/controlpanel.php?e=baddata" );
	}
	
	$user = seekPOST('u');
	$pass = seekPOST('p');
	$passResetToken = seekPOST('t');
	$newpass1 = seekPOST('x');
	$newpass2 = seekPOST('y');
	
	if( strlen( $newpass1 ) < 2 || 
		strlen( $newpass2 ) < 2 )
	{
		header( "Location: http://" . AT_HOST . "/controlpanel.php?e=badnewpass" );
	}
	else if( $newpass1 !== $newpass2 )
	{
		header( "Location: http://" . AT_HOST . "/controlpanel.php?e=passinequal" );
	}
	else
	{
		if( isset( $passResetToken ) && IsValidPasswordResetToken($user, $passResetToken) )
		{
			RemovePasswordResetToken($user, $passResetToken);
			
			if( changePassword( $user, $newpass1 ) )
			{
				//	Perform auto-login:
				generateCookie($user, $newCookie);
				RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions );
				
				header( "Location: http://" . AT_HOST . "/controlpanel.php?e=changepassok" );
			}
			else
			{
				header( "Location: http://" . AT_HOST . "/controlpanel.php?e=generalerror" );
			}
		}
		else if( validateUser( $user, $pass, $fbUser, 0 ) == TRUE )
		{
			if( changePassword( $user, $newpass1 ) )
			{
				header( "Location: http://" . AT_HOST . "/controlpanel.php?e=changepassok" );
			}
			else
			{
				header( "Location: http://" . AT_HOST . "/controlpanel.php?e=generalerror" );
			}
		}
		else
		{
			header( "Location: http://" . AT_HOST . "/controlpanel.php?e=badpass" );
		}
	}
?>
