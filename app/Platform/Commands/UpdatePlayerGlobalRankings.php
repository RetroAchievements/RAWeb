<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Platform\Jobs\UpdatePlayerGlobalRankingsJob;
use Illuminate\Console\Command;

class UpdatePlayerGlobalRankings extends Command
{
    protected $signature = 'ra:platform:player:update-global-rankings';
    protected $description = 'Dispatch a job to rebuild materialized player global rankings';

    public function handle(): int
    {
        UpdatePlayerGlobalRankingsJob::dispatch();

        $this->info('Dispatched the player global rankings rebuild job.');

        return self::SUCCESS;
    }
}
