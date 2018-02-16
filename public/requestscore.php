<?php require_once __DIR__ . '/../lib/bootstrap.php';
   
	// http://php.net/manual/en/security.database.sql-injection.php
	
	$user = $_POST["User"];
	
	return getScore( $user );
?>
