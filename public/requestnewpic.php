<?php
	require_once __DIR__ . '/../lib/bootstrap.php';
	
	$user = $_POST["User"];
	$pass = $_POST["Pass"];
	$hashed = $_POST["Hash"];
	
	//	Assume salted: don't pass plaintext passwords anywhere!!!
	$pwSalted = $pass;
	
	if( loginFromApp( $user, $pwSalted, $scoreOut, $messagesOut ) )
	{
		echo "OK:" . $scoreOut . ":" . $messagesOut;
		return TRUE;
	}
	else
	{
		echo "FAILED: Invalid User/Password combination.\n";
		return FALSE;
	}
?>
