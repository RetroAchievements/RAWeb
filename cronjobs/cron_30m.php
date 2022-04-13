<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

$playersList = getCurrentlyOnlinePlayers();
$numPlayers = is_countable($playersList) ? count($playersList) : 0;

echo "$numPlayers\r\n";
