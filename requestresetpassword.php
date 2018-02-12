<?php 
	require_once('db.inc.php');
	 
	if( !ValidatePOSTChars( "u" ) )
	{
		error_log( __FILE__ );
		error_log( "Cannot validate u input..." );
		header( "Location: http://" . AT_HOST . "/index.php?e=baddata" );
	}
	
	error_log( __FILE__ );
		
	$user = seekPOST('u');
	RequestPasswordReset($user);
	header( "Location: http://" . AT_HOST . "/index.php?e=checkyouremail" );
?>
