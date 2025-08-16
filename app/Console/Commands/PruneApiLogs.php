<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ApiLogEntry;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PruneApiLogs extends Command
{
    protected $signature = 'ra:db:api:prune-logs 
                            {--days=14 : Number of days to retain logs}';
    protected $description = 'Remove stale API logs (enforce data retention policy)';

    public function handle(): void
    {
        $retentionDays = (int) $this->option('days');
        $chunkSize = 1000;

        $cutoffDate = Carbon::now()->subDays($retentionDays);

        $this->info("Pruning API logs older than {$retentionDays} days (before {$cutoffDate->toDateTimeString()})...");

        $totalDeleted = 0;

        // Delete stuff in chunks to avoid memory issues and lock contention.
        do {
            $deleted = ApiLogEntry::where('created_at', '<', $cutoffDate)
                ->limit($chunkSize)
                ->delete();

            $totalDeleted += $deleted;

            if ($deleted > 0) {
                $this->info("Deleted {$deleted} records (total: {$totalDeleted})");
            }
        } while ($deleted > 0);

        $this->info("Pruning complete. Total records deleted: {$totalDeleted}");
    }
}
