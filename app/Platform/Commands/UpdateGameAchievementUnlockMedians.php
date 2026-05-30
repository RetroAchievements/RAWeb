<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Game;
use App\Platform\Actions\UpdateGameAchievementUnlockMediansAction;
use Illuminate\Console\Command;

class UpdateGameAchievementUnlockMedians extends Command
{
    protected $signature = 'ra:platform:game:update-achievement-unlock-medians
                            {gameIds : Comma-separated list of game IDs}';
    protected $description = "Update achievement unlock medians for game(s)";

    public function __construct(
        private readonly UpdateGameAchievementUnlockMediansAction $updateGameAchievementUnlockMedians,
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
            $this->updateGameAchievementUnlockMedians->execute($game);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->line(PHP_EOL);
    }
}
