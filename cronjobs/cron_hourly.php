<?php

use Illuminate\Support\Facades\Log;

$playersOnline = count(getCurrentlyOnlinePlayers());

Log::info('cron_hourly', ['playersOnline' => $playersOnline]);
