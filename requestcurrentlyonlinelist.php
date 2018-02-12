<?php
	require_once('db.inc.php');
	
	$playersList = getCurrentlyOnlinePlayers();
	echo json_encode( $playersList );
	
?>