<?php 
	require_once('db.inc.php');
	
	if( !ValidatePOSTChars( "u" ) )
	{
		header( "Location: http://" . AT_HOST . "/controlpanel.php?e=invalidparams" );
		exit;
	}

	$user = seekPOST( 'u' );
	
	if( recalcScore( $user ) )
	{
		header( "Location: http://" . AT_HOST . "/controlpanel.php?e=recalc_ok" );
		exit;
	}
	else
	{
		header( "Location: http://" . AT_HOST . "/controlpanel.php?e=recalc_error" );
		exit;
	}
?>