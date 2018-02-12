<?php
	require_once('db.inc.php');
	
	$user = seekPOST( 'u', NULL );
	$gameID = seekPOST( 'g', NULL );
	$activityMessage = seekPOST( 'm', NULL );
	
	if( isset( $user ) )
	{
		userActivityPing( $user );
		
		if( isset( $gameID ) && isset( $activityMessage ) )
			UpdateUserRichPresence( $user, $gameID, $activityMessage );
	}
?>