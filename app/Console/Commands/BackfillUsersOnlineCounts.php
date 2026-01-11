<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\UsersOnlineCount;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class BackfillUsersOnlineCounts extends Command
{
    protected $signature = 'ra:site:user:backfill-online-counts';
    protected $description = 'Backfill users online counts from the legacy log file';

    private const LOG_PATH = 'logs/playersonline.log';
    private const LOG_INTERVAL_MINUTES = 30;

    public function handle(): void
    {
        $path = storage_path(self::LOG_PATH);
        if (!file_exists($path)) {
            $this->error('Log file not found: ' . $path);

            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (empty($lines)) {
            $this->error('Log file is empty.');

            return;
        }

        $lineCount = count($lines);

        // Calculate timestamps by working backward from the file's last modified time.
        // The log file only stores counts, so we infer timestamps from the known interval.
        $lastModified = Carbon::createFromTimestamp(filemtime($path));
        $firstEntryTime = $lastModified->copy()->subMinutes(self::LOG_INTERVAL_MINUTES * ($lineCount - 1));

        $this->info("Found {$lineCount} entries.");
        $this->info("Calculated time range: {$firstEntryTime} to {$lastModified}");

        $records = [];
        $allTimeHigh = 0;
        $allTimeHighIndex = 0;
        foreach ($lines as $index => $line) {
            $count = (int) $line;
            $timestamp = $firstEntryTime->copy()->addMinutes(self::LOG_INTERVAL_MINUTES * $index);

            $records[] = [
                'online_count' => $count,
                'created_at' => $timestamp,
            ];

            if ($count > $allTimeHigh) {
                $allTimeHigh = $count;
                $allTimeHighIndex = $index;
            }
        }

        $allTimeHighDate = $firstEntryTime->copy()->addMinutes(self::LOG_INTERVAL_MINUTES * $allTimeHighIndex);
        $this->info("All-time high: {$allTimeHigh} at {$allTimeHighDate}");

        foreach (array_chunk($records, 1000) as $chunk) {
            UsersOnlineCount::insert($chunk);
        }

        $this->info("Inserted {$lineCount} records.");
    }
}
