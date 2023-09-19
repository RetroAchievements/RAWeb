<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Platform\Actions\UpdateGameAchievementsMetrics as UpdateGameAchievementsMetricsAction;
use App\Platform\Models\Game;
use Illuminate\Console\Command;

class UpdateGameAchievementsMetrics extends Command
{
    protected $signature = 'ra:platform:game:update-achievement-set-metrics
                            {gameIds : Comma-separated list of game IDs}';
    protected $description = "Update game(s) metrics";

    public function __construct(
        private readonly UpdateGameAchievementsMetricsAction $updateGameAchievementsMetrics
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $gameIds = collect(explode(',', $this->argument('gameIds')))
            ->map(fn ($id) => (int) $id);

        $games = Game::whereIn('id', $gameIds)->get();

        $progressBar = $this->output->createProgressBar($games->count());
        $progressBar->start();

        foreach ($games as $game) {
            $this->updateGameAchievementsMetrics->execute($game);
            $progressBar->advance();
        }

        $progressBar->finish();
    }
}
