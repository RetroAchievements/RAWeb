<?php
	require_once __DIR__ . '/../lib/bootstrap.php';
	
	//	Sanitise!
	$user = $_POST["u"];
	$token = $_POST["t"];
	
	$activityType = $_POST["a"];
	settype( $activityType, 'integer' );
	$message = $_POST["m"];
	
	if( validateUser_app( $user, $token, $fbUser, 0 ) == TRUE )
	{
		if( postActivity( $user, $activityType, $message ) )
		{
			echo "OK";
		}
		else
		{
			echo "FAILED!!";
		}
	}
	else
	{
		echo "FAILED!";
	}
	
?>
