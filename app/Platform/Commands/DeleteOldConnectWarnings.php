<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\ConnectWarning;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class DeleteOldConnectWarnings extends Command
{
    protected $signature = 'ra:platform:player:delete-old-connect-warnings
                            {days=90 : The number of days to keep}';
    protected $description = 'Delete any connect warnings older than the specified number of days';

    public function handle(): void
    {
        $days = (int) $this->argument('days');
        $dateThreshold = Carbon::now()->subDays($days)->startOfDay();

        $deletedCount = ConnectWarning::query()
            ->where('created_at', '<', $dateThreshold)
            ->delete();

        $this->info("Deleted {$deletedCount} connect warnings.");
    }
}
