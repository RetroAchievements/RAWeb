<?php
	require_once('db.inc.php');
	
	if( !ValidatePOSTChars( "utivs" ) )
	{
		echo "FAILED";
		return;
	}
	
	$user 		= seekPOST('u');
	$token 		= seekPOST('t');
	$lbID 		= seekPOST('i');
	$validation = seekPOST('v');	//	Ignore for now?
	$score 		= seekPOST('s');
	
	settype( $lbID, 'integer' );
	settype( $score, 'integer' );
	
	if( validateUser_app( $user, $token, $fbUser, 0 ) == TRUE )
	{
		if( submitLeaderboardEntry( $user, $lbID, $score, $validation, $dataOut ) )
		{
			echo "OK:";
			echo $lbID . ":";
			echo $user . ":";
			echo $dataOut['Score'] . ":";
			echo $dataOut['Rank'] . "\n";
			
			for( $i = 0; $i < 5; $i++ )
			{
				if( isset( $dataOut[$i] ) )
				{
					$timestamp = strtotime( $dataOut[$i]['DateSubmitted'] );
		
					echo $dataOut[$i]['Rank'] . ":" .
						 $dataOut[$i]['User'] . ":" .
						 $dataOut[$i]['Score'] . ":" . 
						 $timestamp . "\n";
				}
			}
		}
		else
		{
			echo "FAILED!";
		}
	}
	else
	{
		echo "FAILED! Cannot validate $user. Token appears invalid.";
	}
?>