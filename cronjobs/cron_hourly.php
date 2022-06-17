<?php

$numPlayers = count(getCurrentlyOnlinePlayers());
$date = date('Y/m/d H:i:s');

echo "[$date] cron_hourly run, $numPlayers online" . PHP_EOL;
