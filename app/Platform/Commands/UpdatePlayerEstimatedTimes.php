<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\PlayerGame;
use App\Platform\Jobs\UpdatePlayerGameMetricsJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class UpdatePlayerEstimatedTimes extends Command
{
    protected $signature = 'ra:platform:player:update-estimated-times';

    protected $description = 'Updates estimated play times for player_games';

    public function handle(): void
    {
        $playerGames = PlayerGame::whereNull('playtime_total');
        $count = $playerGames->count();

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        $playerGames->chunkById(1000, function ($chunk) use ($progressBar) {
            $jobs = $chunk->map(function (PlayerGame $playerGame) {
                return new UpdatePlayerGameMetricsJob($playerGame->user_id, $playerGame->game_id);
            })->all();

            Bus::batch($jobs)
                ->name('Update player estimated times')
                ->onQueue('player-game-metrics-batch')
                ->allowFailures()
                ->dispatch();

            $progressBar->advance(count($chunk));
        });
    }
}
