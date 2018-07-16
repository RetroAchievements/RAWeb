<?php
	require_once __DIR__ . '/../lib/bootstrap.php';
	
	RA_ClearCookie( 'RA_User' );
	RA_ClearCookie( 'RA_Cookie' );
	
	$redir = $_GET['Redir'];
	header( "Location: " . getenv('APP_URL') . $redir );
?>
