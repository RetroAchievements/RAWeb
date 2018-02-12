<?php require_once('db.inc.php');
   
	// http://php.net/manual/en/security.database.sql-injection.php
	
	if( !ValidatePOSTChars( "utav" ) )
	{
		echo "FAILED";
		return;
	}
	
	$user = seekPOST( 'u' );
	$token = seekPOST( 't' );
	$achIDToAward = seekPOST( 'a' );
	settype( $achIDToAward, 'integer' );
	$validation = seekPOST( 'v' );
	$hardcore = seekPOST( 'h', 0 );

	if( validateUser_app( $user, $token, $fbUser, 0 ) == TRUE )
	{
		if( getAccountDetails( $user, $dataOut ) )
		{
			if( ( $dataOut['fbPrefs'] & 2 ) == 0 )
			{
				//	We do not have the authority to post achievements to fb!
				//	Instead, replace fbUser with zero, and pass this instead to addAchievement, so that it won't post anything to fb.
				$fbUser = 0;
			}
			
			if( addEarnedAchievement( $user, $validation, $achIDToAward, $fbUser, $newPointTotal, $hardcore, false ) )
			{
				//	Great
				echo $newPointTotal;
			}
			else
			{
				//echo "FAILED";
			}
		}
	}
	else
	{
		echo "INVALID USER/PASS!";
	}
?>
