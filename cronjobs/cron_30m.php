<?php

use Illuminate\Support\Facades\Log;

$playersOnline = count(getCurrentlyOnlinePlayers());

Log::info('cron_hourly', ['playersOnline' => $playersOnline]);

file_put_contents(storage_path('logs/playersonline.log'), $playersOnline . PHP_EOL, FILE_APPEND);
