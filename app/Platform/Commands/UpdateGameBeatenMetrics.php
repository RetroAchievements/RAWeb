<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Game;
use App\Platform\Actions\UpdateGameBeatenMetricsAction;
use App\Platform\Jobs\UpdateGameBeatenMetricsJob;
use Illuminate\Console\Command;

class UpdateGameBeatenMetrics extends Command
{
    protected $signature = 'ra:platform:game:update-beaten-metrics
                            {gameIds? : Optional comma-separated list of game IDs}';
    protected $description = "Update beaten metrics for all games or a comma-separated list of game IDs";

    public function __construct(
        private readonly UpdateGameBeatenMetricsAction $updateGameBeatenMetrics
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $query = Game::query();

        $gameIds = null;
        if ($this->argument('gameIds')) {
            $gameIds = collect(explode(',', $this->argument('gameIds')))->map(fn ($id) => (int) $id);
            $query->whereIn('id', $gameIds);
        }

        $totalGames = $query->count();

        if ($totalGames === 0) {
            $this->info('No games found.');

            return;
        }

        if ($this->argument('gameIds') !== null) {
            $this->info("Processing {$totalGames} games...");
        } else {
            $this->info("Dispatching jobs for {$totalGames} games...");
        }

        $progressBar = $this->output->createProgressBar($totalGames);
        $progressBar->start();

        $processed = 0;

        $query->chunk(100, function ($games) use (&$processed, $progressBar) {
            foreach ($games as $game) {
                if ($this->argument('gameIds') === null) {
                    dispatch(new UpdateGameBeatenMetricsJob($game->id))->onQueue('game-beaten-metrics');
                } else {
                    $this->updateGameBeatenMetrics->execute($game);
                }

                $processed++;
                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->newLine(2);

        if ($this->argument('gameIds')) {
            $this->info("Processed {$totalGames} games successfully.");
        } else {
            $this->info("Dispatched {$totalGames} jobs successfully.");
        }
    }
}
