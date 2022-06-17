<?php

deleteExpiredEmailVerificationTokens();
deleteOverdueUserAccounts();
deleteOrphanedLeaderboardEntries();

$date = date('Y/m/d H:i:s');
echo "[$date] cron_daily run\r\n";
