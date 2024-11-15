<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Game;
use App\Platform\Actions\UpdateGamePlayerGamesAction;
use Illuminate\Console\Command;

class UpdateGamePlayerGames extends Command
{
    protected $signature = 'ra:platform:game:update-player-games
                            {gameIds : Comma-separated list of game IDs}';
    protected $description = "Update game(s) outdated player game metrics";

    public function __construct(
        private readonly UpdateGamePlayerGamesAction $updateGamePlayerGames
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
            $this->updateGamePlayerGames->execute($game);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->line(PHP_EOL);
    }
}
