<?php 
	require_once __DIR__ . '/../lib/bootstrap.php';
   
	getCookie( $user, $cookie );
	if( validateUser_cookie( $user, $cookie, 4 ) )
	{
		
	}
	else
	{
		echo "INVALID USER/PASS!";
	}
?>
