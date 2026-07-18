<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Game;
use App\Platform\Actions\UpdateGameAchievementUnlockMediansAction;
use App\Platform\Jobs\UpdateGameAchievementUnlockMediansJob;
use Illuminate\Console\Command;

class UpdateGameAchievementUnlockMedians extends Command
{
    protected $signature = 'ra:platform:game:update-achievement-unlock-medians
                            {gameIds? : Optional comma-separated list of game IDs}';
    protected $description = "Update achievement unlock medians for game(s)";

    public function __construct(
        private readonly UpdateGameAchievementUnlockMediansAction $updateGameAchievementUnlockMedians,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        if ($this->argument('gameIds')) {
            $gameIds = collect(explode(',', $this->argument('gameIds')))->map(fn ($id) => (int) $id);
            $query = Game::whereIn('id', $gameIds);
        } else {
            $query = Game::where('achievements_published', '>', 0);
        }

        $count = $query->count();
        $dispatch = ($count > 20);

        if ($dispatch) {
            $this->info("Dispatching jobs for {$count} games...");
        } else {
            $this->info("Processing {$count} games...");
        }

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        $query->chunk(100, function ($games) use ($progressBar, $dispatch) {
            foreach ($games as $game) {
                if ($dispatch) {
                    dispatch(new UpdateGameAchievementUnlockMediansJob($game->id))->onQueue('achievement-metrics');
                } else {
                    $this->updateGameAchievementUnlockMedians->execute($game);
                }

                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->line(PHP_EOL);
    }
}
