<?php require_once('db.inc.php');
   
	// http://php.net/manual/en/security.database.sql-injection.php
	
	$user = $_POST["User"];
	
	return getScore( $user );
?>
