<?php 
	require_once __DIR__ . '/../lib/bootstrap.php';
	 
	if( !ValidatePOSTChars( "u" ) )
	{
		error_log( __FILE__ );
		error_log( "Cannot validate u input..." );
		header( "Location: " . APP_URL . "/index.php?e=baddata" );
	}
	
	error_log( __FILE__ );
		
	$user = seekPOST('u');
	RequestPasswordReset($user);
	header( "Location: " . APP_URL . "/index.php?e=checkyouremail" );
?>
