<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Leaderboard;
use App\Platform\Actions\RecalculateLeaderboardTopEntryAction;
use Illuminate\Console\Command;

class SyncLeaderboardTopEntries extends Command
{
    protected $signature = 'ra:sync:leaderboard-top-entries';
    protected $description = 'Sync denormalized top entry data in the leaderboard definitions table';

    public function handle(): void
    {
        $this->info('Starting sync of leaderboard top entries...');

        $query = Leaderboard::query()->whereNull('deleted_at');

        $total = $query->count();
        $this->info("Found {$total} leaderboards to process.");

        if ($total === 0) {
            $this->info('No leaderboards to process.');

            return;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunkById(5000, function ($leaderboards) use ($bar) {
            /** @var Leaderboard $leaderboard */
            foreach ($leaderboards as $leaderboard) {
                (new RecalculateLeaderboardTopEntryAction())->execute($leaderboard->id);
                $bar->advance();
            }
        }, 'ID');

        $bar->finish();
        $this->newLine(2);

        $this->info("Done.");
    }
}
