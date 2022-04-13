<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

$playersList = getCurrentlyOnlinePlayers();
$numPlayers = is_countable($playersList) ? count($playersList) : 0;

settype($numPlayers, 'integer');

$date = date('Y/m/d H:i:s');
echo "[$date] cron_hourly run, $numPlayers online\r\n";
