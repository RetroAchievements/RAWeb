<?php
	require_once __DIR__ . '/../lib/bootstrap.php';
	
	$playersList = getCurrentlyOnlinePlayers();
	echo json_encode( $playersList );
	
?>
