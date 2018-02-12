<?php 
	require_once('db.inc.php');
   
	getCookie( $user, $cookie );
	if( validateUser_cookie( $user, $cookie, 4 ) )
	{
		
	}
	else
	{
		echo "INVALID USER/PASS!";
	}
?>
