<?php require_once __DIR__ . '/../lib/bootstrap.php';
	
	//	Auto login from app uses token. Standard login from app uses password.
	$user = seekPOST( 'u', NULL );
	$pass = seekPOST( 'p', NULL );
	$token = seekPOST( 't', NULL );
	
	$response = array();
	
	$errorCode = login_appWithToken( $user, $pass, $token, $scoreOut, $messagesOut );
	settype( $response['Success'], 'boolean' );
	
	if( $errorCode == -1 )
	{
		$response['Success'] = FALSE;
		$response['Error'] = "Automatic login failed (token expired), please login manually!\n";
	}
	else if( $errorCode == 1 )
	{
		$response['Success'] = TRUE;
		$response['User'] = $user;
		$response['Token'] = $token;
		$response['Score'] = $scoreOut;
		$response['Messages'] = $messagesOut;
	}
	else
	{
		$response['Success'] = FALSE;
		$response['Error'] = "Invalid User/Password combination. Please try again\n";
		error_log( "requestlogin failed: $pass, $token, $success" );
	}
	
	echo json_encode( $response );
?>
