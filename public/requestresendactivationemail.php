<?php 
	require_once __DIR__ . '/../lib/bootstrap.php';
   
	$user = seekGet( 'u' );
	
	getAccountDetails( $user, $userDetails );
	$emailAddr = $userDetails['EmailAddress'];
	
	if( sendValidationEmail( $user, $emailAddr ) == FALSE )
	{
		error_log( __FILE__ . " cannot send validation email to this user!?" );
		header( "Location: http://" . AT_HOST . "/?e=accountissue" );
	}
	
	header( "Location: http://" . AT_HOST . "/?e=validateEmailPlease" );
?>
