<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\User;
use App\Platform\Jobs\UpdatePlayerWeightedPointsJob;
use Illuminate\Console\Command;

class CrawlPlayerWeightedPoints extends Command
{
    protected $signature = 'ra:platform:player:crawl-weighted-points
                            {--chunk=2000 : Number of users to process per chunk}';
    protected $description = 'Dispatch jobs to update weighted points for all players';

    public function handle(): void
    {
        $chunkSize = (int) $this->option('chunk');
        $maxUserId = (int) User::max('id');
        $totalChunks = (int) ceil($maxUserId / $chunkSize);

        $this->info("Dispatching {$totalChunks} jobs to player-points-stats-batch...");

        for ($chunk = 0; $chunk < $totalChunks; $chunk++) {
            $startId = $chunk * $chunkSize;
            $endId = ($chunk + 1) * $chunkSize - 1;
            UpdatePlayerWeightedPointsJob::dispatch($startId, $endId);
        }

        $this->info("Dispatched.");
    }
}
