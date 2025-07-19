<?php

declare(strict_types=1);

namespace App\Community\Commands;

use App\Models\UserActivity;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DeleteOldUserActivities extends Command
{
    protected $signature = 'ra:community:user-activity:delete-old {--days=90 : Number of days to keep user activities}';
    protected $description = 'Delete user activity records older than specified days (default: 90).';

    public function handle(): void
    {
        $days = (int) $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);

        $this->info("Deleting user activities older than {$days} days (before {$cutoffDate->format('Y-m-d H:i:s')})...");

        $query = UserActivity::where('created_at', '<', $cutoffDate);
        $totalCount = $query->count();

        if ($totalCount === 0) {
            $this->info('No user activities found to delete.');

            return;
        }

        $this->info("Found {$totalCount} user activities to delete.");

        // If we don't delete in chunks, we can run into memory issues.
        $deletedCount = 0;
        $chunkSize = 1000;
        while (true) {
            $affectedRows = UserActivity::where('created_at', '<', $cutoffDate)
                ->limit($chunkSize)
                ->delete();

            if ($affectedRows === 0) {
                break;
            }

            $deletedCount += $affectedRows;
            $this->info("Deleted {$deletedCount} / {$totalCount} user activities...");
        }

        $this->info("Successfully deleted {$deletedCount} user activities.");
    }
}
