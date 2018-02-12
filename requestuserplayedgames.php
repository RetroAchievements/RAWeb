<?php 
	require_once('db.inc.php');
   
	if( !ValidatePOSTChars( "u" ) )
	{
		echo "ERROR";
		exit;
	}

	$user = seekPOST( 'u' );
	
	getcookie( $userIn, $cookie );
	if( $user == $userIn && validateUser_cookie( $user, $cookie, 0 ) == FALSE )
	{
		echo "ERROR2";
		exit;
	}
	
	if( getControlPanelUserInfo( $user, $userData ) )
	{
		echo json_encode( $userData['Played'] );
	}
	else
	{
		echo "ERROR3";
	}
?>
