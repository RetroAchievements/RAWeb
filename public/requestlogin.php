<?php
	require_once __DIR__ . '/../lib/bootstrap.php';
	
	$user = seekPOST( 'u', NULL );
	
	//$pass = "";
	//$token = "";
	
	//	Auto login from app uses token. Standard login from app uses password.
	
    //error_log( var_dump( $_POST ) );
    
	$pass = seekPOST( 'p', NULL );
	$token = seekPOST( 't', NULL );
	
	$success = login_appWithToken( $user, $pass, $token, $scoreOut, $messagesOut );
	settype( $success, "integer" );
	
	if( $success == -1 )
	{
		echo "FAILED: $success Token expired: please login again!\n";
		return FALSE;
	}
	else if( $success == 1 )
	{
		echo "OK:" . $token . ":" . $scoreOut . ":" . $messagesOut;
		return TRUE;
	}
	else
	{
		error_log( "requestlogin failed: $pass, $token, $success" );
		echo "FAILED: Invalid User/Password combination\n";
		return FALSE;
	}
?>
