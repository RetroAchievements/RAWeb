<?php
	require_once __DIR__ . '/../lib/bootstrap.php';
	
	if( !ValidatePOSTChars( "utmg" ) )
	{
		echo "FAILED";
		return;
	}
	
	$user = $_POST["u"];
	$token = $_POST["t"];
	$md5 = $_POST["m"];
	$gameNameAlt = $_POST['g'];
	
	$consoleID = seekPOST( 'c', '1' );
	
	//	Somewhat elevated privileges to submit or rename a game title:
	if( validateUser_app( $user, $token, $fbUser, 1 ) == TRUE )
	{
		if( submitAlternativeGameTitle( $user, $md5, $gameNameAlt, $consoleID, $idOut ) )
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
	}
?>
