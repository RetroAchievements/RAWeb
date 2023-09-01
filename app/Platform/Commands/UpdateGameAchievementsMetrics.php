<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Platform\Actions\UpdateGameAchievementsMetricsAction;
use App\Platform\Models\Game;
use Illuminate\Console\Command;

class UpdateGameAchievementsMetrics extends Command
{
    protected $signature = 'ra:platform:game:update-achievement-set-metrics
                            {gameIds : Comma-separated list of game IDs}';
    protected $description = "Update game(s) metrics";

    public function handle(): void
    {
        $gameIds = collect(explode(',', $this->argument('gameIds')))
            ->map(fn ($id) => (int) $id);

        $games = Game::whereIn('id', $gameIds)->get();

        $progressBar = $this->output->createProgressBar($games->count());
        $progressBar->start();

        foreach ($games as $game) {
            app()->make(UpdateGameAchievementsMetricsAction::class)->execute($game);
            $progressBar->advance();
        }

        $progressBar->finish();
    }
}
