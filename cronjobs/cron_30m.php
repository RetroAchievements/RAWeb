<?php 
	require_once('/var/www/html/db.inc.php');
	
	//$output = shell_exec('crontab -l');
	//echo "<pre>$output</pre>";
	//error_log( "Cron Job Run!" );
	
	$playersList = getCurrentlyOnlinePlayers();
	$numPlayers = count( $playersList );
	
	//$date = date('Y/m/d H:i:s');
	//echo "[$date] cron_30m run, $numPlayers online\r\n";
	echo "$numPlayers\r\n";
?>
