<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

//$output = shell_exec('crontab -l');
//echo "<pre>$output</pre>";
//error_log( "Cron Job Run!" );

$playersList = getCurrentlyOnlinePlayers();
$numPlayers = count($playersList);

settype($numPlayers, 'integer');

$date = date('Y/m/d H:i:s');
echo "[$date] cron_hourly run, $numPlayers online\r\n";
