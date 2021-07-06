<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

deleteOverdueUserAccounts();
deleteOrphanedLeaderboardEntries();

$date = date('Y/m/d H:i:s');
echo "[$date] cron_daily run\r\n";
