<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Platform\Enums\GlobalRankingWindow;
use App\Platform\Jobs\UpdatePlayerGlobalRankingsJob;
use Illuminate\Console\Command;

class UpdatePlayerGlobalRankings extends Command
{
    protected $signature = 'ra:platform:player:update-global-rankings';
    protected $description = 'Dispatch jobs to rebuild materialized player global rankings';

    public function handle(): int
    {
        foreach (GlobalRankingWindow::cases() as $window) {
            UpdatePlayerGlobalRankingsJob::dispatch($window)->onQueue('game-beaten-metrics');
        }

        $this->info('Dispatched ' . count(GlobalRankingWindow::cases()) . ' player global rankings jobs.');

        return self::SUCCESS;
    }
}
