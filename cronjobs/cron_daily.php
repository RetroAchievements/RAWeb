<?php

use Illuminate\Support\Facades\Log;

deleteExpiredEmailVerificationTokens();
deleteOverdueUserAccounts();
deleteOrphanedLeaderboardEntries();

Log::info('cron_daily');
