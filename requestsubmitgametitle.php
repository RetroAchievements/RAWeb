<?php
	require_once('db.inc.php');
	
	if( !ValidatePOSTChars( "cutmg" ) )
	{
		echo "FAILED";
		return;
	}
    
	$consoleID = filter_input( INPUT_POST, "c" );
	$user = $_POST["u"];
	$token = $_POST["t"];
	$md5 = $_POST["m"];
	$givenTitle = $_POST["g"];
	
	error_log( "Attempting to submit '$givenTitle' via requestsubmitgametitle.php" );
	
	//	Somewhat elevated privileges to submit or rename a game title:
	if( validateUser_app( $user, $token, $fbUser, 1 ) == TRUE )
	{
		if( submitGameTitle( $user, $md5, $givenTitle, $consoleID, $idOut ) )
		{
			echo "OK:" . $idOut;
		}
		else
		{
			echo "FAILED!";
		}
	}
	else
	{
		echo "FAILED! Cannot validate $user. Have you confirmed your email?";
		error_log( "requestsubmitgametitle.php: $user, $token, $md5, '$givenTitle', $consoleID" );
	}
?>