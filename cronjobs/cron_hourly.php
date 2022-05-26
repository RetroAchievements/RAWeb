<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

$numPlayers = count(getCurrentlyOnlinePlayers());
$date = date('Y/m/d H:i:s');

echo "[$date] cron_hourly run, $numPlayers online" . PHP_EOL;
